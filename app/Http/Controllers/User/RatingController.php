<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Rating;
use App\Models\Booking;
use App\Models\Activity;
use App\Models\ActivityPlan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RatingController extends Controller
{
    /**
     * Store a new rating.
     */

    // public function store(Request $request)
    // {
    //     // Validate the request data
    //     $validated = $request->validate([
    //         'activity_id' => 'required|exists:activities,id',
    //         'rating' => 'required|integer|min:1|max:5',
    //         'comment' => 'nullable|string',
    //     ]);

    //     // Fetch the authenticated user
    //     $user_id = auth()->id();
    //     $user_name = auth()->user()->name;

    //     // Check if the user has booked this activity
    //     $booking = Booking::where('user_id', $user_id)
    //         ->where('activity_id', $validated['activity_id'])
    //         ->first();

    //     if (!$booking) {
    //         return response()->json(['error' => 'You have not booked this activity.'], 400);
    //     }

    //     // Fetch the activity
    //     $activity = Activity::findOrFail($validated['activity_id']);

    //     // Check if the activity has ended (for single-day activities) or the booking date has passed (for plan-based activities)
    //     $now = Carbon::now();
    //     $activityEnded = false;

    //     if (empty($activity->activity_days) && empty($activity->activity_times)) {
    //         // Single-day activity: Check if the start_date has passed
    //         // $activityEnded = $now->gt(Carbon::parse($activity->start_date));
    //          // Check if the booking date has passed

    //         $bookingDate = Carbon::parse($booking->date);
    //         $activityEnded = true; // Assume the activity has ended unless proven otherwise
    //         // dd($bookingDate);
    //         if ($now->lt($bookingDate)) {
    //             return response()->json(['error' => 'You can only rate this activity after the booking date has passed.'], 400);
    //         }
    //     } else {
    //         // Plan-based activity: Check if all activity dates have passed
    //         $activityDates = explode(',', $activity->activity_days);
    //         $activityEnded = true; // Assume the activity has ended unless proven otherwise

    //         foreach ($activityDates as $date) {
    //             $activityDateTime = Carbon::parse($date);

    //             if ($now->lt($activityDateTime)) {
    //                 $activityEnded = false; // Activity has not ended
    //                 break;
    //             }
    //         }
    //     }

    //     if (!$activityEnded) {
    //         return response()->json(['error' => 'You can only rate this activity after it has ended.'], 400);
    //     }

    //     // Check if the user has already rated this activity
    //     $existingRating = Rating::where('user_id', $user_id)
    //         ->where('activity_id', $validated['activity_id'])
    //         ->first();

    //     if ($existingRating) {
    //         return response()->json(['error' => 'You have already rated this activity.'], 400);
    //     }

    //     // Add user details to the validated data
    //     $validated['user_id'] = $user_id;
    //     $validated['user_name'] = $user_name;

    //     // Create the rating
    //     $rating = Rating::create($validated);

    //     return response()->json([
    //         'message' => 'Rating submitted successfully.',
    //         'data' => $rating,
    //     ], 201);
    // }

    public function store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500', // Added max length for comment
        ]);

        // Fetch the authenticated user
        $user = auth()->user();
        $user_id = $user->id;
        $user_name = $user->name;

        // Check if the user has booked this activity
        $booking = Booking::where('user_id', $user_id)
            ->where('activity_id', $validated['activity_id'])
            ->first();

        if (!$booking) {
            return response()->json([
                'error' => 'You have not booked this activity.',
                'error_ar' => 'لم يتم حجز هذه الرحلة.',
            ], 400);
        }

        // Fetch the activity
        $activity = Activity::findOrFail($validated['activity_id']);

        // Check if the activity has ended based on its type
        $now = Carbon::now('UTC'); // Use UTC timezone for consistency
        $activityEnded = false;

        // Determine activity type
        if (!empty($activity->activity_days) && !empty($activity->activity_times)) {
            // Plan-based activity: Check if all activity dates have passed
            $activityDates = explode(',', $activity->activity_days);
            $activityEnded = true; // Assume the activity has ended unless proven otherwise

            foreach ($activityDates as $date) {
                // Parse each date individually
                $activityDateTime = Carbon::parse($date, 'UTC'); // Use UTC timezone

                // Get the day name from the date (e.g., "Monday", "Tuesday", etc.)
                $dayName = $activityDateTime->dayName;

                // Get the current day name
                $currentDayName = $now->dayName;

                // Compare the day names
                if ($dayName === $currentDayName) {
                    // If the day names match, check if the activity date has passed
                    if ($now->lt($activityDateTime)) {
                        $activityEnded = false; // Activity has not ended
                        break;
                    }
                }
            }
        } else {
            // Single-day activity: Check if the booking date and time have passed
            $bookingDateTime = Carbon::parse($booking->date . ' ' . $booking->time, 'UTC'); // Use UTC timezone
            if ($now->lt($bookingDateTime)) {
                return response()->json([
                    'error' => 'You can only rate this trip after the booking date and time have passed.',
                    'error_ar' => 'لا يمكنك تقييم هذه الرحلة إلا بعد مرور تاريخ ووقت الحجز.'
                ], 400);
            }
            $activityEnded = true; // Booking date and time have passed, so the activity is considered ended
        }

        if (!$activityEnded) {
            return response()->json([
                'error' => 'You can only rate this trip after it has ended.',
                'error_ar' => 'لا يمكنك تقييم هذه الرحلة إلا بعد انتهائه.',
            ], 400);
        }

        // Check if the user has already rated this activity
        $existingRating = Rating::where('user_id', $user_id)
            ->where('activity_id', $validated['activity_id'])
            ->exists();

        if ($existingRating) {
            return response()->json([
                'error' => 'You have already rated this trip.',
                'error_ar' => 'لقد قمت بالفعل بتقييم هذه الرحلة.',
            ], 400);
        }

        // Add user details to the validated data
        $validated['user_id'] = $user_id;
        $validated['user_name'] = $user_name;

        // Create the rating
        $rating = Rating::create($validated);

        return response()->json([
            'message' => 'Rating submitted successfully.',
            'message_ar' => 'تم إرسال التقييم بنجاح.',
            'data' => $rating,
        ], 201);
    }




    public function show_reviews($activity_id, $perPage)
    {
        // Validate 'perPage' parameter
        if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
            return response()->json([
                'message' => 'Invalid perPage parameter. Must be between 1 and 100.',
                'message_ar' => 'معلمة لكل صفحة غير صالحة. يجب أن يكون بين 1 و100.',
            ], 400);
        }

        // Fetch the activity details
        $activity = Activity::find($activity_id);

        if (!$activity) {
            return response()->json([
                'message' => 'Trip not found',
                'message_ar' => 'لم يتم العثور على الرحلة',
            ], 404);
        }

        // Fetch and paginate the ratings for the activity
        $ratings = Rating::/*query()
        ->with('user:id,name,email')*/ // Ensure 'user' relation exists
            where('activity_id', $activity_id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        // dd($ratings);

        // Check if no reviews are found
        if ($ratings->isEmpty()) {
            return response()->json([
                'message' => 'No reviews found for the given Trip',
                'message_ar' => 'لم يتم العثور على أي تعليقات للرحلة المحدد',
            ], 404);
        }

        // Calculate average rating and total reviews
        // $averageRating = round(Rating::where('activity_id', $activity_id)->avg('rating'), 2);
        // $totalReviews = Rating::where('activity_id', $activity_id)->count();

        // // Attach additional details to each rating
        // $ratings->getCollection()->each(function ($rating) use ($averageRating, $totalReviews) {
        //     $rating->average_rating = $averageRating;
        //     $rating->total_reviews = $totalReviews;
        // });

        $activity->service_provider = User::where('id', $activity->user_id)->first();

        // Calculate average rating and total reviews
        $averageRating = number_format(Rating::where('activity_id', $activity_id)->avg('rating'), 4, '.', ',');
        $totalReviews = Rating::where('activity_id', $activity_id)->count();

        // Attach additional details to each rating
        $ratings->getCollection()->each(function ($rating) use ($averageRating, $totalReviews) {
            $rating->average_rating = $averageRating;
            $rating->total_reviews = $totalReviews;
            $rating->time_ago = Carbon::parse($rating->created_at)->diffForHumans();
            $rating->time_ago_ar = Carbon::parse($rating->created_at)->locale('ar')->diffForHumans();
        });

        // Return the response
        return response()->json([
            'Activity' => $activity,
            'ratings' => $ratings,
        ], 200);
    }
}
