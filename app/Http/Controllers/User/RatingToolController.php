<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Rating;
use App\Models\CommercialTool;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RatingToolController extends Controller
{
    /**
     * Store a new rating.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            // 'user_id' => 'required|exists:users,id',
            'tool_id' => 'required|exists:commercial_tools,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'guest_email' => 'nullable|email',
            'guest_name' => 'nullable|string|max:255',
        ]);
        // Fetch the tool and its times
        $tool = CommercialTool::findOrFail($validated['tool_id']);

        $user_id = auth()->id();
        $user_name = auth()->user()->name;
        // $user_email = auth()->user()->email;

        // Check if authenticated user has already rated this tool

        $existingRating = Rating::where('user_id', auth()->id())
            ->where('tool_id', $validated['tool_id'])
            ->first();

        if ($existingRating) {
            return response()->json([
                'error' => 'You have already rated this Commercial Tool.',
                'error_ar' => 'لقد قمت بالفعل بتقييم هذه الأداة التجارية.',
            ], 400);
        }

        $validated['user_name'] = $user_name;
        $validated['user_id'] = $user_id;


        // Create the rating
        $rating = Rating::create($validated);
        // dd($rating);

        return response()->json([
            'message' => 'Rating submitted successfully.',
            'message_ar' => 'تم إرسال التقييم بنجاح.',
            // 'data'=>$rating,
        ], 201);
    }



    public function show_reviews($tool_id, $perPage)
    {
        // Validate 'perPage' parameter
        if (!is_numeric($perPage) || $perPage < 1 || $perPage > 100) {
            return response()->json([
                'message' => 'Invalid perPage parameter. Must be between 1 and 100.',
                'message_ar' => 'معلمة لكل صفحة غير صالحة. يجب أن يكون بين 1 و100.',
            ], 400);
        }

        // Fetch the tool details
        $tool = CommercialTool::find($tool_id);

        if (!$tool) {
            return response()->json([
                'message' => 'Tool not found',
                'message_ar' => 'لم يتم العثور على الأداة',
            ], 404);
        }

        // Fetch and paginate the ratings for the tool
        $ratings = Rating::/*query()
        ->with('user:id,name,email')*/ // Ensure 'user' relation exists
            where('tool_id', $tool_id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        // dd($ratings);

        // Check if no reviews are found
        if ($ratings->isEmpty()) {
            return response()->json([
                'message' => 'No reviews found for the given Tool',
                'message_ar' => 'لم يتم العثور على أي تعليقات للأداة المحددة',
            ], 404);
        }

        // Calculate average rating and total reviews
        $averageRating = round(Rating::where('tool_id', $tool_id)->avg('rating'), 2);
        $totalReviews = Rating::where('tool_id', $tool_id)->count();

        // Attach additional details to each rating
        $ratings->getCollection()->each(function ($rating) use ($averageRating, $totalReviews) {
            $rating->average_rating = $averageRating;
            $rating->total_reviews = $totalReviews;
        });


        // Calculate average rating and total reviews
        $averageRating = number_format(Rating::where('tool_id', $tool_id)->avg('rating'), 4, '.', ',');
        $totalReviews = Rating::where('tool_id', $tool_id)->count();

        // Attach additional details to each rating
        $ratings->getCollection()->each(function ($rating) use ($averageRating, $totalReviews) {
            $rating->average_rating = $averageRating;
            $rating->total_reviews = $totalReviews;
            $rating->time_ago = Carbon::parse($rating->created_at)->diffForHumans();
            $rating->time_ago_ar = Carbon::parse($rating->created_at)->locale('ar')->diffForHumans();
        });


        // Return the response
        return response()->json([
            'Tool' => $tool,
            'ratings' => $ratings,
        ], 200);
    }
}
