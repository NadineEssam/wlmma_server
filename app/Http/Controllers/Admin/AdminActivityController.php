<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AActivityStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\StoreActivityTypes;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Requests\Activity\UpdateActivityTypes;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityImage;
use App\Models\ActivityPlan;
use App\Models\ActivityTool;
use App\Models\ActivityType;
use App\Models\Booking;
use App\Models\CommercialTool;
use App\Models\Image;
use App\Models\Order;
use App\Models\Tool;
use App\Models\ToolAttribute;
use App\Models\ToolAttributeValues;
use App\Models\ToolImage;
use App\Models\User;
use App\Models\WaitingList;
use App\Services\Activity\ActivityService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AdminActivityController extends Controller
{
    public function __construct(
        private ActivityService $service
    ) {}

    /* Activities */

    // Show all Activities
    public function admin_index(Request $request)
    {
        $activities = $this->service->getAllActivitiesForAdmin($request->per_page);

        return response()->json($activities);
    }

    // Add Activity
    public function admin_store(StoreActivityRequest $request)
    {
        DB::beginTransaction();

        // try {
        // Get the days from the request (e.g., [2, 5, 9])
        $days = $request->input('start_date');

        $activity_times_start = [];
        $activity_times_end = [];
        $activity_days = [];
        $activity_capacity_input = $request->capacity;

        if ($request->plan_activity == 'no') {  // single activity
            $activity_times_start = array_filter($request->input('activity_times_start') ?? []);
            $activity_times_end = array_filter($request->input('activity_times_end') ?? []);
            $activity_days = array_filter($request->input('activity_days') ?? []);
            $activityDays = is_array($activity_days) ? $activity_days : explode(',', $activity_days);
        }

        // Prepare activity days and times
        $activityDays = $activity_days ? (is_array($activity_days) ? $activity_days : explode(',', $activity_days)) : [];
        $activityTimesStart = $activity_times_start ? (is_array($activity_times_start) ? $activity_times_start : explode(',', $activity_times_start)) : [];
        $activityTimesEnd = $activity_times_end ? (is_array($activity_times_end) ? $activity_times_end : explode(',', $activity_times_end)) : [];
        // $activity_capacity_input = array_filter($request->input('capacity') ?? []);
        // $activityCapacities = is_array($activity_capacity_input) ? $activity_capacity_input : explode(',', $activity_capacity_input);

        $availableTimes = [];
        $activity_single_dates = '';

        // Generate dates for the entire year starting from the first start date
        if (!empty($activityDays) && !empty($activityTimesStart)) {
            // Get the first start date (parse the first date if multiple provided)
            $firstDate = is_array($days) ? $days[0] : $days;
            $startDate = Carbon::parse(trim($firstDate));
            $endDate = $startDate->copy()->addYear();  // Generate for 1 year from start date

            // Create a period for each day in the year
            $period = CarbonPeriod::create($startDate, $endDate);

            foreach ($period as $date) {
                $dayName = strtolower($date->format('l'));

                // Check if this day is in our activity days
                foreach ($activityDays as $index => $day) {
                    if (strtolower(trim($day)) === $dayName) {
                        $timeIndex = $index;

                        if (isset($activityTimesStart[$timeIndex])) {
                            $availableTimes[] = [
                                'date' => $date->toDateString(),
                                'day_name' => $date->format('l'),
                                'start_time' => $activityTimesStart[$timeIndex],
                                'end_time' => $activityTimesEnd[$timeIndex] ?? null,
                                'capacity' => $activityCapacities[$index] ?? 0,
                                'source' => 'generated_from_input',
                            ];
                        }
                    }
                    $activity_single_dates = array_column($availableTimes, 'date');
                }
            }
        }
        // dd($activity_single_dates);
        // Now build the activity data array
        $activityData = [
            'user_id' => $request->user_id,
            'user_type' => User::find($request->user_id)->userType->type,
            'title_en' => $request->title_en,
            'spoken_lang' => $request->spoken_lang,
            'title_ar' => $request->title_ar,
            'description_en' => $request->description_en,
            'description_ar' => $request->description_ar,
            'city_name_en' => $request->city_name_en,
            'city_name_ar' => $request->city_name_ar,
            'country_name_en' => $request->country_name_en,
            'country_name_ar' => $request->country_name_ar,
            'privacy_policy_en' => $request->privacy_policy_en,
            'privacy_policy_ar' => $request->privacy_policy_ar,
            'cancel_policy_en' => $request->cancel_policy_en,
            'cancel_policy_ar' => $request->cancel_policy_ar,
            'duration' => $request->duration,
            'plan_activity' => $request->plan_activity,
            'price' => $request->price,
            'capacity' => $activity_capacity_input,
            'status_id' => AActivityStatusEnum::ACTIVE,
            'lat' => $request->lat,
            'long' => $request->long,
            'is_tourguideable' => $request->is_tourguideable,
            'tourguide_price' => $request->tourguide_price,
            'activity_type_id' => $request->activity_type_id,
            // 'start_date' => is_array($days) ? implode(',', $days) : $days,
            'start_date' => $days,
            'is_photographer_available' => $request->is_photographer_available,
            'photographer_price' => $request->photographer_price,
            'activity_days' => is_array($request->activity_days) && count($request->activity_days) > 0
                ? implode(',', $request->activity_days)
                : null,
            'activity_times_start' => is_array($activity_times_start) && count($activity_times_start) > 0
                ? implode(',', $activity_times_start)
                : null,
            'activity_times_end' => is_array($activity_times_end) && count($activity_times_end) > 0
                ? implode(',', $activity_times_end)
                : null,
            // 'activity_single_dates' => json_encode($activity_single_dates), // Store the generated available times
            'activity_single_dates' => is_array($activity_single_dates) ? implode(',', $activity_single_dates) : $activity_single_dates,
        ];

        // Create the activity
        $activity = Activity::create($activityData);

        // [Rest of your existing code for activity plans, images, tools, etc...]
        // Add activity plans if provided
        if ($request->filled('activity_plans')) {
            $activityPlansData = [];

            foreach ($request->activity_plans as $index => $plan) {
                // $day = isset($days[$index]) ? Carbon::parse($days[$index])->format('j') : null;
                $day = isset($days[$index]) && strtotime($days[$index]) !== false
                    ? Carbon::parse($days[$index])->format('j')
                    : null;

                $activityPlansData[] = [
                    'activity_id' => $activity->id,
                    'city_name_en' => $plan['location_en'],
                    'city_name_ar' => $plan['location_ar'],
                    'starts_at' => $plan['starts_at'],
                    'ends_at' => $plan['ends_at'],
                    'dates' => $days,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ActivityPlan::insert($activityPlansData);
        }

        // Handle images
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $imagePaths = [];

            foreach ($images as $image) {
                $filename = uniqid() . '.' . $image->getClientOriginalExtension();
                $destinationPath = storage_path('app/public/activities/' . $filename);

                list($width, $height) = getimagesize($image);
                $newWidth = 800;
                $newHeight = intval($height * ($newWidth / $width));

                $sourceImage = match ($image->getClientOriginalExtension()) {
                    'jpg', 'jpeg' => imagecreatefromjpeg($image),
                    'png' => imagecreatefrompng($image),
                    'webp' => imagecreatefromwebp($image),
                    default => null,
                };

                if ($sourceImage) {
                    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                    switch ($image->getClientOriginalExtension()) {
                        case 'jpg':
                        case 'jpeg':
                            imagejpeg($resizedImage, $destinationPath, 75);
                            break;
                        case 'png':
                            imagepng($resizedImage, $destinationPath, 6);
                            break;
                        case 'webp':
                            imagewebp($resizedImage, $destinationPath, 75);
                            break;
                    }

                    imagedestroy($sourceImage);
                    imagedestroy($resizedImage);

                    $path = 'activities/' . $filename;

                    $imagePaths[] = [
                        'image_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            Image::insert($imagePaths);

            $lastId = Image::orderByDesc('id')->first()?->id ?: 0;
            $ids = range($lastId - count($imagePaths) + 1, $lastId);

            $imageActivity = [];
            foreach ($ids as $id) {
                $imageActivity[] = [
                    'image_id' => $id,
                    'activity_id' => $activity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ActivityImage::insert($imageActivity);
        }

        // Handle tools
        if ($request->filled('tool_id')) {
            foreach ($request->tool_id as $toolId) {
                ActivityTool::create([
                    'commercial_tool_id' => $toolId,
                    'activity_id' => $activity->id,
                ]);
            }
        }
        DB::commit();

        // Re-fetch the activity with all relations
        $newActivity = Activity::with(['activityImages', 'activityPlans', 'activityTools', 'activityTools.toolImages'])
            ->select(
                'activities.*',
                'activity_statuses.status'
            )
            // ->where('user_id', auth()->id())
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->findOrFail($activity->id);

        // Format the available times for the response
        if (!empty($newActivity->available_times)) {
            $newActivity->available_times = json_decode($newActivity->available_times, true);
        } else {
            $newActivity->available_times = $availableTimes;
        }

        // Format image URLs
        $newActivity->activityImages->each(function ($image) {
            $image->image_path = Str::startsWith($image->image_path, ['http://', 'https://'])
                ? $image->image_path
                : env('APP_URL') . 'storage/app/public/' . $image->image_path;
        });

        // Format tool images
        // foreach ($newActivity->tools as $tool) {
        //     if ($tool->activityTools && $tool->activityTools->images) {
        //         $tool->commercialTool->images->each(function ($image) {
        //             $image->image_path = Str::startsWith($image->image_path, ['http://', 'https://'])
        //                 ? $image->image_path
        //                 : env('APP_URL') . 'storage/app/public/' . $image->image_path;
        //         });
        //     }
        // }

        $activityTools = ActivityTool::where('activity_id', $newActivity->id)->get();

        $toolIds = $activityTools->pluck('commercial_tool_id');

        $tools = CommercialTool::whereIn('id', $toolIds)->with('toolImages')->get();

        $tools->transform(function ($tool) {
            // Convert tool_images to an indexed array with full image path
            $tool->tool_images = $tool->toolImages->map(function ($image) {
                $image->image_path = Str::startsWith($image->image_path, ['http://', 'https://'])
                    ? $image->image_path
                    : rtrim(env('APP_URL'), '/') . '/storage/app/public/' . ltrim($image->image_path, '/');
                return $image;
            })->values();

            return $tool;
        });

        // Attach transformed tools to the activity

        $newActivity->tools = $tools->values();
        // Send notification
        // $notificationData = [
        //     'title_en' => 'New Trip Added',
        //     'title_ar' => 'تم إضافة رحلة جديد',
        //     'body_en' => 'A new activity has been added to the system.',
        //     'body_ar' => 'تم إضافة رحلة جديد إلى النظام.',
        //     'type' => 'general',
        //     'action' => 'activity',
        //     'send_to_all' => true,
        // ];

        // $pushController = new NotificationController();
        // $pushController->push(new PushNotificationRequest($notificationData));

        return response()->json([
            'message' => 'Trip created successfully',
            'message_ar' => 'تم إنشاء الرحلة بنجاح',
            'data' => $newActivity
        ], 201);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     info($e);
        //     return response()->json([
        //         'message' => 'An error occurred while processing your request.',
        //         'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
        //         'error' => $e->getMessage(),
        //     ], 500);
        // }
    }

    /**
     * Generate dates for the specified days across multiple months and years.
     *
     * @param array $days Array of days (e.g., [2, 5, 9])
     * @return array Array of generated dates (e.g., ["2025-01-02", "2025-01-05", "2025-01-09", ...])
     */
    // ORIGINAL
    // private function generateDatesForDays(array $days,$duration): array
    // {
    //     $startDate = now()->startOfMonth();
    //     $endDate = $startDate->copy()->addYears(2);
    //     $generatedDates = [];
    //     while ($startDate->lte($endDate)) {
    //         foreach ($days as $day) {
    //             if (checkdate($startDate->month, $day, $startDate->year)) {
    //                 $generatedDates[] = Carbon::create($startDate->year, $startDate->month, $day)->toDateString();
    //             }
    //         }
    //         $startDate->addMonth();
    //     }
    //     return $generatedDates;
    // }
    private function generateDatesForDays(Carbon|string $day, int $duration, $activity_type): array
    {
        // Ensure $day is a Carbon instance and get start of its month
        $startDate = is_string($day) ? Carbon::parse($day)->startOfMonth() : $day->copy()->startOfMonth();

        $endDate = $startDate->copy()->addYears(2);
        $startDay = (int) Carbon::parse($day)->day;
        $startDay_single = $day;

        $generatedDates = [];
        if ($activity_type == 'no') {
            $generatedDates = [$startDay_single];  // now it's an array
        } else {
            // while ($startDate->lte($endDate)) {
            for ($i = 0; $i < $duration; $i++) {
                $targetDay = $startDay + $i;

                if (checkdate($startDate->month, $targetDay, $startDate->year)) {
                    $generatedDates[] = Carbon::create(
                        $startDate->year,
                        $startDate->month,
                        $targetDay
                    )->toDateString();
                }
            }
            // $startDate->addMonth();
            // }
        }
        // dd($generatedDates);
        return $generatedDates;
    }

    // Update Activity Data
    public function admin_update(UpdateActivityRequest $request, Activity $activity)
    {
        $activity = $this->service->updateActivity($activity, $request->only([
            'title_en',
            'title_ar',
            'description_ar',
            'description_en',
            'location',
            // 'time',
            // 'duration',
            'price',
            'capacity',
            'status_id',
        ]));

        // return response()->json($activity);

        $activity = Activity::with('activityImages')
            ->select(
                'activities.id',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'description_en',
                'description_ar',
                'city_name_en',
                'city_name_ar',
                'country_name_en',
                'country_name_ar',
                'duration',
                'plan_activity',
                'start_date',
                'price',
                'activities.spoken_lang',
                'capacity',
                'status',
                'lat',
                'long',
                'is_tourguideable',
                'tourguide_price',
                'is_photographer_available',
                'photographer_price',
                'activity_days',
                'activity_times_start',
                'activity_times_end',
                'privacy_policy_en',
                'privacy_policy_ar',
                'cancel_policy_en',
                'cancel_policy_ar',
                'activity_single_dates'
            )
            // ->where('user_id', auth()->id())
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->where('activities.id', $activity->id)
            ->firstOrFail();

        // Calculate average rating
        $ratings = $activity->rate->pluck('rating');  // Assuming 'rate' has a 'rating' field
        $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0;  // Calculate average or default to 0
        $activity->average_rating = number_format($averageRating, 1, '.', ',');;  // Add average rating to the activity response add show only one number after point

        $activity->rate = $activity->rate->map(function ($rate) {
            // $rate->name = $rate->user ? $rate->user->name : null; // Add user name
            // $rate->user_email = $rate->user ? $rate->user->email : null; // Add user email
            $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();  // Add time_ago
            $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
            return $rate;
        });

        // Process available_times for the activity
        $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
        $activityTimesStart = $activity->activity_times_start ? explode(',', $activity->activity_times_start) : [];
        $activityTimesEnd = $activity->activity_times_end ? explode(',', $activity->activity_times_end) : [];
        $activitySingleDates = $activity->activity_single_dates ? explode(',', $activity->activity_single_dates) : [];
        // dd($activitySingleDates);
        $availableTimes = [];

        foreach ($activitySingleDates as $dateString) {
            try {
                $date = Carbon::parse($dateString);
                $dayName = strtolower($date->format('l'));

                // ابحث عن اليوم داخل activityDays
                foreach ($activityDays as $index => $day) {
                    if (strtolower(trim($day)) === $dayName) {
                        $availableTimes[] = [
                            'date' => $date->toDateString(),
                            'day_name' => $date->format('l'),
                            'start_time' => $activityTimesStart[$index] ?? null,
                            'end_time' => $activityTimesEnd[$index] ?? null,
                        ];
                        break;  // وجدنا اليوم، لا داعي لإكمال اللوب
                    }
                }
            } catch (\Exception $e) {
                // تجاهل الأخطاء الناتجة عن تواريخ غير صحيحة
                continue;
            }
        }

        $activity->available_times = $availableTimes;

        // Fetch related data in bulk to optimize the number of queries
        $activityIds = [$activity->id];  // Since this is a single activity, we only need its ID

        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = Tool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Attach related data to the activity
        $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();  // Ensure it's an indexed array

        $tools = $activityTools->where('activity_id', $activity->id);
        $tools->transform(function ($tool) /* use ($toolsAttributes, $toolAttributeValues) */ {
            // Convert tool_attributes to an indexed array
            // $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
            //     $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values();  // Ensure values is an indexed array
            //     return $att;
            // })->values();  // Ensure tool_attributes is an indexed array

            // Convert tool_images to an indexed array
            $tool->tool_images = $tool->toolImages->map(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            })->values();  // Ensure tool_images is an indexed array

            return $tool;
        });

        $activity->tools = $tools->values();  // Ensure tools is an indexed array

        // Attach images with full paths
        if ($activity->relationLoaded('activityImages')) {
            $activity->activityImages->transform(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            });
        }

        if ($request->status_id == 3) {
            $all_booking_users = WaitingList::where('activity_id', $activity->id)->pluck('user_id')->toArray();
            WaitingList::where('activity_id', $activity->id)
                ->whereIn('user_id', $all_booking_users)  // If you want to update specific users
                ->update(['status' => 'accepted']);

            // dd($all_booking_users);

            $notificationData = [
                'title_en' => 'Trip is Accepted',
                'title_ar' => 'تم تفعيل هذا الرحلة',
                'body_en' => 'This Trip "' . $activity->title_en . '" with id = ' . $activity->id . ' is Accepted',
                'body_ar' => "تم تفعيل هذا الرحلة '{$activity->title_ar}' مع المعرف = {$activity->id}",
                'type' => 'reservation_accepted',
                'action' => 'update_activity',
                'send_to_all' => false,
            ];

            // dd($notificationData);
            // Call the push_book method from the notification controller
            $pushController = new NotificationController();  // Create an instance of the notification controller
            // foreach ($all_booking_users as $all_users) {
            $pushController->push_book_many(new PushNotificationRequest($notificationData), $all_booking_users);
            // }
        }
        // Return the activity with related data
        // return response()->json($activity);

        return response()->json([
            'message' => 'Trip updated successfully',
            'message_ar' => 'تم تحديث الرحلة بنجاح',
            'data' => $activity
        ], 200);
    }

    // Show Specific Activity data
    public function show($id)
    {
        try {
            // Fetch the activity with related data
            $activity = Activity::with('activityImages', 'activityType')
                ->select(
                    'activities.id',
                    'activities.user_id',
                    'activity_type_id',
                    'title_en',
                    'title_ar',
                    'description_en',
                    'description_ar',
                    'city_name_en',
                    'city_name_ar',
                    'country_name_en',
                    'country_name_ar',
                    'duration',
                    'plan_activity',
                    'start_date',
                    'price',
                    'activities.spoken_lang',
                    'capacity',
                    'status',
                    'lat',
                    'long',
                    'is_tourguideable',
                    'tourguide_price',
                    'is_photographer_available',
                    'photographer_price',
                    'activity_days',
                    'activity_times_start',
                    'activity_times_end',
                    'privacy_policy_en',
                    'privacy_policy_ar',
                    'cancel_policy_en',
                    'cancel_policy_ar',
                    'activity_single_dates'
                )
                ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
                ->where('activities.id', $id)
                ->firstOrFail();

            // Calculate average rating
            $ratings = $activity->rate->pluck('rating');  // Assuming 'rate' has a 'rating' field
            $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0;  // Calculate average or default to 0
            // $activity->average_rating = number_format($averageRating, 1, '.', ','); // Add average rating to the activity response add show only one number after point
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

            $activity->rate = $activity->rate->map(function ($rate) {
                // $rate->name = $rate->user ? $rate->user->name : null; // Add user name
                // $rate->user_email = $rate->user ? $rate->user->email : null; // Add user email
                $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();  // Add time_ago
                $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
                return $rate;
            });

            // Process available_times for the activity
            $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
            $activityTimesStart = $activity->activity_times_start ? explode(',', $activity->activity_times_start) : [];
            $activityTimesEnd = $activity->activity_times_end ? explode(',', $activity->activity_times_end) : [];
            $activitySingleDates = $activity->activity_single_dates ? explode(',', $activity->activity_single_dates) : [];
            // dd($activitySingleDates);
            $availableTimes = [];

            foreach ($activitySingleDates as $dateString) {
                try {
                    $date = Carbon::parse($dateString);
                    $dayName = strtolower($date->format('l'));

                    // ابحث عن اليوم داخل activityDays
                    foreach ($activityDays as $index => $day) {
                        if (strtolower(trim($day)) === $dayName) {
                            $availableTimes[] = [
                                'date' => $date->toDateString(),
                                'day_name' => $date->format('l'),
                                'start_time' => $activityTimesStart[$index] ?? null,
                                'end_time' => $activityTimesEnd[$index] ?? null,
                            ];
                            break;  // وجدنا اليوم، لا داعي لإكمال اللوب
                        }
                    }
                } catch (\Exception $e) {
                    // تجاهل الأخطاء الناتجة عن تواريخ غير صحيحة
                    continue;
                }
            }

            $activity->available_times = $availableTimes;

            // Fetch related data in bulk to optimize the number of queries
            $activityIds = [$activity->id];  // Since this is a single activity, we only need its ID

            $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
            $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
            $toolIds = $activityTools->pluck('commercial_tool_id');  // Extract all IDs into an array
            // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
            // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

            // Attach related data to the activity
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();  // Ensure it's an indexed array

            $tools = CommercialTool::whereIn('id', $toolIds)->get();
            $tools->transform(function ($tool) /* use ($toolsAttributes, $toolAttributeValues) */ {
                // Convert tool_attributes to an indexed array
                // $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
                //     $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values();  // Ensure values is an indexed array
                //     return $att;
                // })->values();  // Ensure tool_attributes is an indexed array

                // Convert tool_images to an indexed array
                $tool->tool_images = $tool->toolImages->map(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                })->values();  // Ensure tool_images is an indexed array

                return $tool;
            });

            $activity->tools = $tools->values();  // Ensure tools is an indexed array

            // Attach images with full paths
            if ($activity->relationLoaded('activityImages')) {
                $activity->activityImages->transform(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                });
            }

            return response()->json([
                'data' => $activity
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Trip not found',
                'message_ar' => 'لم يتم العثور على الرحلة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred',
                'message_ar' => 'حدث خطأ غير متوقع'
            ], 500);
        }
    }

    // Delete Activity
    public function admin_destroy($id)
    {
        $hasBookings = Booking::where('activity_id', $id)->exists();
        if (!$hasBookings) {
            // try {
            $activity = Activity::findOrFail($id);
            if ($activity) {
                // Access related activity plans
                $activityPlans = ActivityPlan::where('activity_id', $id);  // Correct way to access the relationship

                // Delete activity plans
                foreach ($activityPlans as $plan) {
                    $plan->delete();
                }

                // Access related tools
                $tools = Tool::where('activity_id', $id);  // Correct way to access the relationship

                // Delete tools
                foreach ($tools as $tool) {
                    $tool->delete();
                }
            }

            // Delete the activity itself
            $activity->delete();

            return response()->json([
                // 'message' => __('SUCCESS'),
                'message' => "Trip and it's plans and tools has been deleted successfully",
                'message_ar' => 'تم حذف الرحلة وخططها وأدواتها بنجاح',
            ], 200);
            // } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            //     return response()->json([
            //         'message' => 'Trip not found',
            //         'message_ar' => 'لم يتم العثور على الرحلة',
            //     ], 404);
            // } catch (\Exception $e) {
            //     return response()->json([
            //         'message' => 'An error occurred while processing your request.',
            //         'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
            //         'error' => $e->getMessage(),
            //     ], 500);
            // }
        } else {
            return response()->json([
                // 'message' => __('SUCCESS'),
                'message' => 'Trip has bookings',
                'message_ar' => 'هذه الرحلة لديها حجوزات',
            ], 200);
        }
    }

    public function featured_trips(Request $request)
    {
        $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'at_home' => ['required', 'in:yes,no'],
        ]);

        $today = Carbon::today()->toDateString();

        $activity = Activity::where('id', $request->activity_id)
            ->where(function ($q) use ($today) {
                $q
                    ->where('start_date', '>', $today)
                    ->orWhere('activity_single_dates', '>', $today);
            })
            ->first();

        if (!$activity) {
            return response()->json([
                'message' => 'The date of this trip is today or before today.',
                'message_ar' => 'تاريخ هذه الرحلة هو اليوم او قبل اليوم',
            ], 404);
        }

        $activity->update([
            'at_home' => $request->at_home
        ]);

        return response()->json([
            'message' => 'Trip updated successfully',
            'message_ar' => 'تم تحديث الرحلة بنجاح',
            'data' => $activity
        ], 200);
    }

    /* Activities */

    /* ActivityTypes */

    // Show all ActivityTypes
    public function activityTypes(Request $request)
    {
        $activityTypes = $this->service->getActivityTypes($request->per_page);

        return response()->json($activityTypes);
    }

    // Store ActivityType
    public function storeActivityTypes(StoreActivityTypes $request)
    {
        try {
            DB::beginTransaction();

            // Pass the validated data and image file to the service
            $activityType = $this->service->creatActivityTypes($request->validated(), $request->file('image'));

            DB::commit();
            // return response()->json($activityType, 201);
            return response()->json([
                'message' => 'Success',
                'Data' => $activityType,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            info($e);
            return response()->json([], 500);
        }
    }

    // Show Specific activityType data
    public function showActivityTypes(ActivityType $activityType)
    {
        $activityType = $this->service->findActivityType($activityType->id);

        // return response()->json($activityType);
        return response()->json([
            'message' => 'Success',
            'Data' => $activityType,
        ], 200);
    }

    // Update activityType Data
    // public function updateActivityTypes(UpdateActivityTypes $request, ActivityType $activityType)
    // {
    //     $activityTypes = $this->service->updateActivityType($activityType, $request->only([
    //         'name_en',
    //         'name_ar',
    //     ]));
    //     return response()->json([
    //         'message' => 'Trip Type Updated successfully',
    //         'message_ar' => 'تم تحديث نوع الرحلة بنجاح',
    //         'data' => $activityTypes
    //     ], 200);
    //     // return response()->json($activityType);
    // }

    public function updateActivityTypes(UpdateActivityTypes $request, ActivityType $activityType)
    {
        $data = $request->only([
            'name_en',
            'name_ar',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $originalExtension = strtolower($image->getClientOriginalExtension());
            $filename = uniqid() . '.' . $originalExtension;
            $storageDir = storage_path('app/public/activityTypes/');
            $storagePath = $storageDir . $filename;

            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $tempPath = $image->getRealPath();

            if (in_array($originalExtension, ['jpg', 'jpeg'])) {
                $imageResource = imagecreatefromjpeg($tempPath);
                imagejpeg($imageResource, $storagePath, 75);  // 75% quality
            } elseif ($originalExtension === 'png') {
                $imageResource = imagecreatefrompng($tempPath);
                imagepng($imageResource, $storagePath, 6);  // compression level 6
            } else {
                throw new \Exception('Unsupported image type');
            }

            imagedestroy($imageResource);

            // delete old image if exists
            if ($activityType->image && \Storage::disk('public')->exists(str_replace('storage/', '', $activityType->image))) {
                \Storage::disk('public')->delete(str_replace('storage/', '', $activityType->image));
            }

            // Store relative public path (e.g., storage/activityTypes/filename.jpg)
            $publicPath = 'storage/app/public/activityTypes/' . $filename;
            $data['image'] = env('APP_URL') . $publicPath;
        } else {
            // Keep old image
            $data['image'] = $activityType->image;
        }

        $activityTypes = $this->service->updateActivityType($activityType, $data);

        return response()->json([
            'message' => 'Trip Type Updated successfully',
            'message_ar' => 'تم تحديث نوع الرحلة بنجاح',
            'data' => $activityTypes
        ], 200);
    }

    // Delete activityType
    public function destroyActivityTypes($id)
    {
        $activityType_data = ActivityType::find($id);

        if (!$activityType_data) {
            return response()->json([
                'message' => 'Trip type not found.',
                'message_ar' => 'نوع الرحلة غير موجود.'
            ], 404);
        }
        if ($activityType_data->activities()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this activity type because it is assigned to some activities.',
                'message_ar' => 'لا يمكن حذف هذا النوع لأنه مرتبط ببعض الرحلات.'
            ], 400);
        } else {
            $activityType = $this->service->deleteActivityType($activityType_data);
        }

        return response()->json([
            'message' => 'Trip Type Deleted successfully',
            'message_ar' => 'تم حذف نوع الرحلة بنجاح',
        ], 200);
    }

    /* ActivityTypes */

    /* Orders */
    public function orders(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $status_id = $request->input('status_id');
        $orders = Order::with(['orderItems', 'orderItems.tool', 'orderItems.tool.toolImages'])->orderBy('created_at', 'desc');

        // if ($status_id == 0) {
        //     $orders->where('created_at', '<', date('Y-m-d'));  // Previous
        // }
        // if ($status_id == 1) {
        //     $orders->where('created_at', '>=', date('Y-m-d'));  // Current & upcoming
        // }

        $orders = $orders->paginate($perPage);

        $orders->transform(function ($order) {
            $order->orderItems->transform(function ($item) {
                // ✅ sub_total for each item
                $item->sub_total = $item->quantity * $item->price;
                if ($item->tool && $item->tool->toolImages) {
                    $item->tool->tool_images = $item->tool->toolImages->map(function ($image) {
                        // $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
                            $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        }
                        return $image;
                    });
                }
                return $item;
            });
            return $order;
        });

        return response()->json([
            'message' => true,
            'data' => $orders
        ],
            200);
    }

    public function order_details($order_id)
    {
        $order = Order::with(['user', 'orderItems', 'orderItems.tool', 'orderItems.tool.toolImages'])
            ->find($order_id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'message_ar' => 'الطلب غير موجود',
            ], 404);
        }

        // Transform order items
        $order->orderItems->transform(function ($item) {
            // ✅ Calculate sub_total for each item
            $item->sub_total = $item->quantity * $item->price;

            if ($item->tool && $item->tool->toolImages) {
                $item->tool->tool_images = $item->tool->toolImages->map(function ($image) {
                    if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    }
                    return $image;
                });
            }

            return $item;
        });

        return response()->json([
            'message' => true,
            'data' => $order
        ], 200);
    }

    /* Orders */

    public function showBookingDetails($id)
    {
        $activity = Booking::with([
            'activity.serviceprovider',
            'activity.activityTools.toolImages',
            'activity.activityType',
            'activity.rate.user',
            'booking_status',
            'customer'
        ])->where('id', $id)->first();

        if (!$activity) {
            return response()->json([
                'message' => 'Not found',
                'message_ar' => 'البيانات ليست موجودة',
            ], 404);
        }

        // Calculate average rating
        $ratings = $activity->rate ? $activity->rate->pluck('rating') : collect();
        $averageRating = $ratings->isNotEmpty() ? $ratings->avg() : 0;
        $activity->average_rating = number_format($averageRating, 4, '.', ',');

        // Enhance rate items with time ago
        if ($activity->rate) {
            $activity->rate = $activity->rate->map(function ($rate) {
                $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();
                $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
                return $rate;
            });
        }

        // Process available times
        $availableTimes = [];
        $days = explode(',', $activity->activity_days ?? '');
        $starts = explode(',', $activity->activity_times_start ?? '');
        $ends = explode(',', $activity->activity_times_end ?? '');
        $singleDates = explode(',', $activity->activity_single_dates ?? '');

        foreach ($singleDates as $dateString) {
            try {
                $date = Carbon::parse($dateString);
                $dayName = strtolower($date->format('l'));

                foreach ($days as $index => $day) {
                    if (strtolower(trim($day)) === $dayName) {
                        $availableTimes[] = [
                            'date' => $date->toDateString(),
                            'day_name' => $date->format('l'),
                            'start_time' => $starts[$index] ?? null,
                            'end_time' => $ends[$index] ?? null,
                        ];
                        break;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $activity->available_times = $availableTimes;

        // Attach activity plans
        $activity->activity_plans = ActivityPlan::where('activity_id', $activity->id)->get();

        // Attach activity tools with image paths
        $activity->activity_tools = $activity->activity->activityTools ?? collect();

        $activity->activity_tools = $activity->activity_tools->map(function ($tool) {
            $tool->tool_images = $tool->toolImages ?? collect();

            $tool->tool_images = $tool->tool_images->map(function ($image) {
                if (!str_starts_with($image->image_path, 'http')) {
                    $image->image_path = env('APP_URL') . 'storage/' . ltrim($image->image_path, '/');
                }
                return $image;
            });

            return $tool;
        });

        // Attach full image paths for activity images
        if ($activity->relationLoaded('activityImages')) {
            $activity->activityImages->transform(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                return $image;
            });
        }

        // Handle selected tools
        $toolIds = $activity->tool_id ? explode(',', $activity->tool_id) : [];
        $capacities = $activity->tool_capacity ? explode(',', $activity->tool_capacity) : [];
        $all_tool_ids = [];

        foreach ($toolIds as $i => $id) {
            $all_tool_ids[(int) trim($id)] = (int) ($capacities[$i] ?? 0);
        }

        $activity->selected_tools = CommercialTool::with('toolImages')
            ->whereIn('id', array_keys($all_tool_ids))
            ->get()
            ->map(function ($tool) use ($all_tool_ids) {
                $tool->capacity = $all_tool_ids[$tool->id] ?? 0;
                $tool->tool_images = $tool->toolImages->map(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                })->values();
                return $tool;
            });

        // Fix provider and customer profile images
        $activity->customer->profileImage = $activity->customer->live_photo
            ? env('APP_URL') . 'storage/' . DB::table('files')->find($activity->customer->live_photo)?->name
            : null;

        $sp = $activity->activity->serviceprovider;
        if ($sp->user_types_id == 3 && $sp->live_photo) {
            $sp->profileImage = env('APP_URL') . 'storage/' . DB::table('files')->find($sp->live_photo)?->name;
        } elseif ($sp->user_types_id == 2 && $sp->company_logo) {
            $sp->profileImage = env('APP_URL') . 'storage/' . DB::table('files')->find($sp->company_logo)?->name;
        } else {
            $sp->profileImage = null;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trip Details',
            'message_ar' => 'تفاصيل الرحلة',
            'data' => $activity,
        ]);
    }

    public function alltripBookings($id)
    {
        $bookings = Booking::with([
            'activity.serviceprovider',
            'activity.activityTools.toolImages',
            'activity.activityType',
            'activity.rate.user',
            'booking_status',
            'customer'
        ])->where('activity_id', $id)->get();

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'Not found',
                'message_ar' => 'البيانات ليست موجودة',
            ], 404);
        }

        $bookings->transform(function ($booking) {
            $activity = $booking->activity;

            // Ensure activity exists
            if (!$activity) {
                return $booking;
            }

            // Average rating
            $ratings = $activity->rate ?? collect();
            $averageRating = $ratings->isNotEmpty() ? $ratings->pluck('rating')->avg() : 0;
            $activity->average_rating = number_format($averageRating, 4, '.', ',');

            // Enhance rate items safely
            $activity->rate = $activity->rate ?? collect();
            $activity->rate->transform(function ($rate) {
                $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();
                $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
                return $rate;
            });

            // Available times
            $availableTimes = [];
            $days = explode(',', $activity->activity_days ?? '');
            $starts = explode(',', $activity->activity_times_start ?? '');
            $ends = explode(',', $activity->activity_times_end ?? '');
            $singleDates = explode(',', $activity->activity_single_dates ?? '');

            foreach ($singleDates as $dateString) {
                try {
                    $date = Carbon::parse($dateString);
                    $dayName = strtolower($date->format('l'));
                    foreach ($days as $index => $day) {
                        if (strtolower(trim($day)) === $dayName) {
                            $availableTimes[] = [
                                'date' => $date->toDateString(),
                                'day_name' => $date->format('l'),
                                'start_time' => $starts[$index] ?? null,
                                'end_time' => $ends[$index] ?? null,
                            ];
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            $activity->available_times = $availableTimes;

            // Activity plans
            $activity->activity_plans = ActivityPlan::where('activity_id', $activity->id)->get();

            // Activity tools with images
            $activity->activity_tools = $activity->activityTools ?? collect();
            $activity->activity_tools = $activity->activityTools->map(function ($tool) {
                $tool->tool_images = $tool->toolImages ?? collect();
                $tool->tool_images->transform(function ($image) {
                    if (!str_starts_with($image->image_path, 'http')) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    }
                    return $image;
                });

                return $tool;
            });

            // Selected tools
            $all_tool_ids = [];
            $toolIds = $activity->tool_id ? explode(',', $activity->tool_id) : [];
            $capacities = $activity->tool_capacity ? explode(',', $activity->tool_capacity) : [];
            foreach ($toolIds as $i => $id) {
                $all_tool_ids[(int) trim($id)] = (int) ($capacities[$i] ?? 0);
            }

            $activity->selected_tools = $activity
                ->activity_tools
                ->map(function ($tool) {
                    $tool->capacity = $tool->capacity ?? 0;  // if you have capacity stored in pivot
                    $tool->tool_images = $tool->tool_images ?? collect();
                    $tool->tool_images->transform(function ($image) {
                        if (!str_starts_with($image->image_path, 'http')) {
                            $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        }
                        return $image;
                    });
                    return $tool;
                });

            // Customer profile image
            $customer = $booking->customer;
            if ($customer && $customer->live_photo) {
                $image = DB::table('files')->find($customer->live_photo);
                $customer->profileImage = $image ? env('APP_URL') . 'storage/' . $image->name : null;
            } elseif ($customer) {
                $customer->profileImage = null;
            }

            // Service provider image/logo
            $provider = $activity->serviceprovider;
            if ($provider) {
                if ($provider->user_types_id == 3 && $provider->live_photo) {
                    $image = DB::table('files')->find($provider->live_photo);
                    $provider->profileImage = $image ? env('APP_URL') . 'storage/' . $image->name : null;
                } elseif ($provider->user_types_id == 2 && $provider->company_logo) {
                    $image = DB::table('files')->find($provider->company_logo);
                    $provider->profileImage = $image ? env('APP_URL') . 'storage/' . $image->name : null;
                } else {
                    $provider->profileImage = null;
                }
            }

            return $booking;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Trip Details',
            'message_ar' => 'تفاصيل الرحلة',
            'data' => $bookings,
        ]);
    }
}
