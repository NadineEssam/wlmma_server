<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityTool;
use App\Models\ActivityType;
use App\Models\Booking;
use App\Models\CommercialTool;
use App\Models\Tool;
use App\Models\ToolAttribute;
use App\Models\ToolAttributeValues;
use App\Services\Activity\ActivityService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserActivityController extends Controller
{
    public function __construct(
        private ActivityService $service
    ) {}

    // public function show(Activity $activity)
    // {
    //     $activities = $this->service->findActivity($activity->id);

    //     return response()->json($activities);
    // }

    public function showBookingDetails($id)
    {
        $booking = Booking::with([
            'activity.activityType',
            'activity.serviceprovider',
            'activity.activityImages',
            'booking_status',
            'customer',
            // 'customer.userImage',
            'activity.rate.user'
        ])
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Not found',
                'message_ar' => 'البيانات ليست موجودة',
            ], 404);
        }

        $activity = $booking->activity;

        if ($activity) {
            // === Ratings ===
            $ratings = $activity->rate ? $activity->rate->pluck('rating') : collect();
            $activity->average_rating = $ratings->isNotEmpty()
                ? number_format($ratings->avg(), 4, '.', ',')
                : 0;

            $activity->rate = $activity->rate ? $activity->rate->map(function ($rate) {
                $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans();
                $rate->time_ago_ar = Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
                return $rate;
            }) : collect();

            // === Available times ===
            $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
            $activityTimesStart = $activity->activity_times_start ? explode(',', $activity->activity_times_start) : [];
            $activityTimesEnd = $activity->activity_times_end ? explode(',', $activity->activity_times_end) : [];
            $activitySingleDates = $activity->activity_single_dates ? explode(',', $activity->activity_single_dates) : [];

            $availableTimes = [];
            foreach ($activitySingleDates as $dateString) {
                try {
                    $date = Carbon::parse($dateString);
                    $dayName = strtolower($date->format('l'));

                    foreach ($activityDays as $index => $day) {
                        if (strtolower(trim($day)) === $dayName) {
                            $availableTimes[] = [
                                'date' => $date->toDateString(),
                                'day_name' => $date->format('l'),
                                'start_time' => $activityTimesStart[$index] ?? null,
                                'end_time' => $activityTimesEnd[$index] ?? null,
                            ];
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            $activity->available_times = $availableTimes;

            // === Attach activity plans ===
            $activity->activity_plans = ActivityPlan::where('activity_id', $activity->id)->get();

            // === Fix activity images ===
            if ($activity->relationLoaded('activityImages')) {
                $activity->activityImages->transform(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                });
            }

            // === Customer image ===
            if ($booking->customer && $booking->customer->relationLoaded('userImage')) {
                $booking->customer->user_image_url = $booking->customer->user_image_url;  // accessor
            }

            // === Tools ===
            $toolIds = !empty($booking->tool_id) ? explode(',', $booking->tool_id) : [];
            $toolCapacities = !empty($booking->tool_capacity) ? explode(',', $booking->tool_capacity) : [];

            $tools = CommercialTool::with('toolImages')
                ->whereIn('id', $toolIds)
                ->get();

            $selected_tools = $tools->map(function ($tool) use ($toolIds, $toolCapacities) {
                $index = array_search($tool->id, $toolIds);
                $tool->capacity = $toolCapacities[$index] ?? 1;

                $tool->tool_images = $tool->toolImages->map(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    return $image;
                })->values();

                return $tool;
            });

            $booking->selected_tools = $selected_tools;

            // Attach provider image if exists
            if ($booking->customer->live_photo) {
                // $path = DB::table('files')->find($user->live_photo)->path;
                $image_name = DB::table('files')->find($booking->customer->live_photo)->name;
                // Retrieve the user's profile image if available
                $profileImage = null;
                // Assuming the image is stored in 'storage/app/public/activities'
                $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
                $booking->customer->live_photo = $profileImage;
                $booking->customer->profileImage = $booking->customer->live_photo;
            } else {
                $profileImage = null;
                $booking->customer->live_photo = $profileImage;
                $booking->customer->profileImage = $booking->customer->live_photo;
            }

            if ($booking->activity->serviceprovider->user_types_id == 3) {  // Indvidual
                if ($booking->activity->serviceprovider->live_photo) {
                    // $path = DB::table('files')->find($user->live_photo)->path;
                    $image_name = DB::table('files')->find($booking->activity->serviceprovider->live_photo)->name;
                    // Retrieve the user's profile image if available
                    $profileImage = null;
                    // Assuming the image is stored in 'storage/app/public/activities'
                    $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
                    $booking->activity->serviceprovider->live_photo = $profileImage;
                    $booking->activity->serviceprovider->profileImage = $profileImage;
                } else {
                    $profileImage = null;
                    $booking->activity->serviceprovider->live_photo = $profileImage;
                    $booking->activity->serviceprovider->profileImage = $profileImage;
                }
            } else if ($booking->activity->serviceprovider->user_types_id == 2) {  // Company
                if ($booking->activity->serviceprovider->company_logo) {
                    // $path = DB::table('files')->find($user->live_photo)->path;
                    $image_name2 = DB::table('files')->find($booking->activity->serviceprovider->company_logo)->name;
                    // Retrieve the user's company_logo if available
                    $company_logo = null;
                    // Assuming the image is stored in 'storage/'
                    $company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
                    $booking->activity->serviceprovider->company_logo = $company_logo;
                    $booking->activity->serviceprovider->profileImage = $company_logo;
                } else {
                    $company_logo = null;
                    $booking->activity->serviceprovider->company_logo = $company_logo;
                    $booking->activity->serviceprovider->profileImage = $company_logo;
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trip Details',
            'message_ar' => 'تفاصيل الرحلة',
            'data' => $booking,
        ]);
    }

    public function show($id)
    {
        try {
            // Fetch the activity with related data
            $activity = Activity::with('activityImages', 'activityType', 'serviceprovider', 'capacities')
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
                    'activity_single_dates',
                    DB::raw('(CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
                )
                // ->where('user_id', auth()->id())
                ->leftJoin('wishlists', function ($join) {
                    $join
                        ->on('wishlists.activity_id', '=', 'activities.id')
                        ->where('wishlists.user_id', auth()->id());
                })
                ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
                ->where('activities.id', $id)
                ->firstOrFail();

            $activity->sameUsersameProvider = ($activity->user_id == auth()->id()) ? 'yes' : 'no';

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

            $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
            $activityTimesStart = $activity->activity_times_start ? explode(',', $activity->activity_times_start) : [];
            $activityTimesEnd = $activity->activity_times_end ? explode(',', $activity->activity_times_end) : [];
            $activitySingleDates = $activity->activity_single_dates ? explode(',', $activity->activity_single_dates) : [];
            // dd($activitySingleDates);
            $availableTimes = [];

            // foreach ($activitySingleDates as $dateString) {
            //     try {
            //         $date = Carbon::parse($dateString);
            //         $dayName = strtolower($date->format('l'));

            //         // ابحث عن اليوم داخل activityDays
            //         foreach ($activityDays as $index => $day) {
            //             if (strtolower(trim($day)) === $dayName) {
            //                 $availableTimes[] = [
            //                     'date' => $date->toDateString(),
            //                     'day_name' => $date->format('l'),
            //                     'start_time' => $activityTimesStart[$index] ?? null,
            //                     'end_time' => $activityTimesEnd[$index] ?? null,
            //                 ];
            //                 break;  // وجدنا اليوم، لا داعي لإكمال اللوب
            //             }
            //         }
            //     } catch (\Exception $e) {
            //         // تجاهل الأخطاء الناتجة عن تواريخ غير صحيحة
            //         continue;
            //     }
            // }
            $from = Carbon::today();
            $to = Carbon::now()->addMonths(3);

            $availableTimes = [];
            // dd($from->toDateString(), $to->toDateString());
            foreach ($activitySingleDates as $dateString) {
                try {
                    $date = Carbon::parse($dateString);

                    // echo($date->between($from, $to) ? $date->toDateString() : null);
                    // تحقق إن التاريخ بين from و to
                    if (!$date->between($from, $to)) {
                        continue;
                    }

                    $dayName = strtolower($date->format('l'));
                    foreach ($activityDays as $index => $day) {
                        if (strtolower(trim($day)) === $dayName) {
                            $availableTimes[] = [
                                'date' => $date->between($from, $to) ? $date->toDateString() : null,
                                'day_name' => $date->format('l'),
                                'start_time' => $activityTimesStart[$index] ?? null,
                                'end_time' => $activityTimesEnd[$index] ?? null,
                            ];
                            break;
                        }
                    }
                } catch (\Exception $e) {
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

            // echo $toolIds;
            $tools = CommercialTool::whereIn('id', $toolIds)->get();
            $tools->transform(function ($tool) /*use ($toolsAttributes, $toolAttributeValues)*/ {
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
                'message' => 'success',
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
                'message_ar' => 'حدث خطأ غير متوقع',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $activities = $this->service->getFilteredActivities($request->all(), $request->per_page);

        return response()->json($activities);
    }

    public function getfeatured_trips(Request $request)
    {
        $activities = $this->service->getfeatured_trips($request->per_page);

        return response()->json($activities);
    }

    public function index_type(Request $request)
    {
        $activities = $this->service->getFilteredActivities_type($request->all(), $request->per_page);

        return response()->json($activities);
    }

    public function addORdeletewishlist(Request $request)
    {
        $activities = $this->service->addORdeletewishlist($request->all(), $request->per_page);

        return response()->json($activities);
    }

    public function wishlist(Request $request)
    {
        $activities = $this->service->get_wishlist($request->per_page);

        return response()->json($activities);
    }

    // public function activityTypes(Request $request)
    // {

    //     $activities = $this->service->getactivityTypes($request->all(), $request->per_page);

    //     return response()->json($activities);
    // }

    public function activityTypes(Request $request)
    {
        $perPage = $request->input('per_page', 15);  // default 15 if not provided

        $activityTypes = $this->service->getactivityTypes($perPage);

        return response()->json($activityTypes);
    }
}
