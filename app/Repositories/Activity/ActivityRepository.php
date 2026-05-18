<?php

namespace App\Repositories\Activity;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityTool;
use App\Models\ActivityType;
use App\Models\Booking;
use App\Models\CommercialTool;
use App\Models\Tool;
use App\Models\ToolAttribute;
use App\Models\ToolAttributeValues;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Carbon;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ActivityRepository
{
    // public function allServiceProviderActivities_old($perPage, $serviceProviderId): LengthAwarePaginator
    // {
    //     $query = Activity::query()->with('activityImages')
    //         ->select(
    //             'activity_type_id',
    //             'title_en',
    //             'title_ar',
    //             'description_en',
    //             'description_ar',
    //             'city_name_en',
    //             'city_name_ar',
    //             'duration',
    //             'price',
    //             'capacity',
    //             'activities.id',
    //             'status',
    //             'is_tourguideable',
    //             'is_photographer_available',
    //             'activity_days'
    //         )
    //         ->where('user_id', $serviceProviderId)
    //         ->orderBy('activities.id', 'desc')
    //         ->join('activity_statuses', 'activity_statuses.id', 'activities.status_id');
    //     $activities = $query->paginate($perPage);
    //     $activityIds = $activities->getCollection()->pluck('id')->toArray();
    //     $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //     $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
    //     $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
    //     $toolAttributeValue = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();
    //     $activities->getCollection()->transform(function ($activity) use ($activityPlans, $activityTools, $toolsAttributes, $toolAttributeValue) {
    //         $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();
    //         $tools = $activityTools->where('activity_id', $activity->id);
    //         $toolOfToolAttributes = $toolsAttributes->whereIn('tool_id', $tools->pluck('id')->toArray());
    //         $toolOfToolAttributes->transform(function ($att) use ($toolAttributeValue) {
    //             $att->values = $toolAttributeValue->where('tool_attribute_id', $att->id)->values();
    //             return $att;
    //         });
    //         $tools->transform(function ($tool) use ($toolOfToolAttributes) {
    //             $tool->tool_attributes = $toolOfToolAttributes->where('tool_id', $tool->id)->values();
    //             return $tool;
    //         });
    //         $activity->tools = $tools->values();
    //         return new ActivityResource($activity);
    //     });

    //     return $activities;
    // }
    public function allServiceProviderActivities($perPage): LengthAwarePaginator
    {
        // Eager load relationships to avoid N+1 queries
        $activities = Activity::with(['activityImages', 'activityType'])
            ->select(
                'activities.id',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'at_home',
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
                'activity_single_dates'
            )
            // ->where('activities.id', 152) // For test
            ->where('user_id', auth()->id())
            ->orderBy('activities.id', 'desc')
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->paginate($perPage);
        // dd($activities);
        // Process each activity
        $activities->each(function ($activity) {
            // Calculate average rating
            $ratings = $activity->rate->pluck('rating');
            // $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 1, '.', ',') : 0;
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

            // Add individual ratings with time_ago
            $activity->rate = $activity->rate->map(function ($rate) {
                return [
                    'rating' => $rate->rating,
                    // 'time_ago' => Carbon::parse($rate->created_at)->diffForHumans(), //doesn't appears in postman
                    $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans(),
                ];
            });
            // dd($activity->rate);
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

            // Update image paths with the full URL
            $activity->activityImages->each(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Fixed the path to /storage/
            });
        });

        // Fetch related data for optimization
        $activityIds = $activities->getCollection()->pluck('id')->toArray();
        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Attach related data to activities
        $activities->getCollection()->each(function ($activity) use ($activityPlans, $activityTools /* , $toolsAttributes, $toolAttributeValues */) {
            // Attach activity plans
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

            // Attach tools with nested attributes and images
            $tools = $activityTools->where('activity_id', $activity->id);
            $formattedTools = [];

            foreach ($tools as $tool) {
                // $toolAttributes = $toolsAttributes->where('tool_id', $tool->id);
                // $formattedAttributes = [];

                // foreach ($toolAttributes as $attribute) {
                //     $values = $toolAttributeValues->where('tool_attribute_id', $attribute->id)->map(function ($value) {
                //         return [
                //             'id' => $value->id,
                //             'tool_attribute_id' => $value->tool_attribute_id,
                //             'value' => $value->value,
                //             'price' => $value->price,
                //         ];
                //     });

                //     $formattedAttributes[] = [
                //         'id' => $attribute->id,
                //         'tool_id' => $attribute->tool_id,
                //         'attribute_name_en' => $attribute->attribute_name_en,
                //         'attribute_name_ar' => $attribute->attribute_name_ar,
                //         'options_values' => $values->values(),  // This will re-index the array starting from 0
                //     ];
                // }

                $formattedTools[] = [
                    'id' => $tool->id,
                    'name_en' => $tool->name_en,
                    'name_ar' => $tool->name_ar,
                    'description_en' => $tool->description_en,
                    'description_ar' => $tool->description_ar,
                    // 'tool_attributes' => $formattedAttributes,
                    'tool_images' => $tool->toolImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
                        ];
                    }),
                ];
            }

            $activity->tools = $formattedTools;
        });

        return $activities;
    }

    public function allActivitiesForAdmin($perPage): LengthAwarePaginator
    {
        // Eager load relationships to avoid N+1 queries
        $activities = Activity::with(['activityImages', 'activityType', 'serviceprovider', 'serviceprovider.userType'])
            ->select(
                'activities.id',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'at_home',
                'description_en',
                'description_ar',
                'city_name_en',
                'city_name_ar',
                'country_name_en',
                'country_name_ar',
                'duration',
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
                'activity_single_dates'
            )
            // ->where('activities.id', 152) // For test
            ->orderBy('activities.id', 'desc')
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->paginate($perPage);
        // dd($activities);
        // Process each activity
        $activities->each(function ($activity) {
            // Calculate average rating
            $ratings = $activity->rate->pluck('rating');
            // $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 1, '.', ',') : 0;
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

            // Add individual ratings with time_ago
            $activity->rate = $activity->rate->map(function ($rate) {
                return [
                    'rating' => $rate->rating,
                    // 'time_ago' => Carbon::parse($rate->created_at)->diffForHumans(), //doesn't appears in postman
                    $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans(),
                ];
            });
            // dd($activity->rate);
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

            // Update image paths with the full URL
            $activity->activityImages->each(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Fixed the path to /storage/
            });
        });

        // Fetch related data for optimization
        $activityIds = $activities->getCollection()->pluck('id')->toArray();
        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Attach related data to activities
        $activities->getCollection()->each(function ($activity) use ($activityPlans, $activityTools /*, $toolsAttributes, $toolAttributeValues*/) {
            // Attach activity plans
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

            // Attach tools with nested attributes and images
            $tools = $activityTools->where('activity_id', $activity->id);
            $formattedTools = [];

            foreach ($tools as $tool) {
                // $toolAttributes = $toolsAttributes->where('tool_id', $tool->id);
                // $formattedAttributes = [];

                // foreach ($toolAttributes as $attribute) {
                //     $values = $toolAttributeValues->where('tool_attribute_id', $attribute->id)->map(function ($value) {
                //         return [
                    //         'id' => $value->id,
                    //         'tool_attribute_id' => $value->tool_attribute_id,
                    //         'value' => $value->value,
                    //         'price' => $value->price,
                    //     ];
                    // });

                    // $formattedAttributes[] = [
                    //     'id' => $attribute->id,
                    //     'tool_id' => $attribute->tool_id,
                    //     'attribute_name_en' => $attribute->attribute_name_en,
                    //     'attribute_name_ar' => $attribute->attribute_name_ar,
                    //     'options_values' => $values->values(),  // This will re-index the array starting from 0
                    // ];
                // }

                $formattedTools[] = [
                    'id' => $tool->id,
                    'name_en' => $tool->name_en,
                    'name_ar' => $tool->name_ar,
                    'description_en' => $tool->description_en,
                    'description_ar' => $tool->description_ar,
                    // 'tool_attributes' => $formattedAttributes,
                    'tool_images' => $tool->toolImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
                        ];
                    }),
                ];
            }

            $activity->tools = $formattedTools;

            // User Image
            //             $image = DB::table('files')->find(138);
            // dd($image);

            $image = DB::table('files')->find($activity->serviceprovider->live_photo);
            // dd($activity->serviceprovider->live_photo); // = 138
            $activity->serviceprovider->profileimage = $image ? env('APP_URL') . 'storage/' . $image->name : null;

            // Company Image
            $image2 = DB::table('files')->find($activity->serviceprovider->company_logo);
            $activity->serviceprovider->companylogo = $image2 ? env('APP_URL') . 'storage/' . $image2->name : null;
        });

        return $activities;
    }

    public function getfeatured_trips($perPage): LengthAwarePaginator
    {
        // Eager load relationships to avoid N+1 queries
        $activities = Activity::with(['activityImages', 'activityType', 'serviceprovider', 'serviceprovider.userType'])
            ->select(
                'activities.id',
                'activities.at_home',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'at_home',
                'description_en',
                'description_ar',
                'city_name_en',
                'city_name_ar',
                'country_name_en',
                'country_name_ar',
                'duration',
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
                'activity_single_dates'
            )
            ->where('at_home', 'yes')
            ->orderBy('activities.id', 'desc')
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->paginate($perPage);
        // dd($activities);
        // Process each activity
        $activities->each(function ($activity) {
            // Calculate average rating
            $ratings = $activity->rate->pluck('rating');
            // $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 1, '.', ',') : 0;
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

            // Add individual ratings with time_ago
            $activity->rate = $activity->rate->map(function ($rate) {
                return [
                    'rating' => $rate->rating,
                    // 'time_ago' => Carbon::parse($rate->created_at)->diffForHumans(), //doesn't appears in postman
                    $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans(),
                ];
            });
            // dd($activity->rate);
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

            // Update image paths with the full URL
            $activity->activityImages->each(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Fixed the path to /storage/
            });
        });

        // Fetch related data for optimization
        $activityIds = $activities->getCollection()->pluck('id')->toArray();
        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Attach related data to activities
        $activities->getCollection()->each(function ($activity) use ($activityPlans, $activityTools /*, $toolsAttributes, $toolAttributeValues*/) {
            // Attach activity plans
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

            // Attach tools with nested attributes and images
            $tools = $activityTools->where('activity_id', $activity->id);
            $formattedTools = [];

            foreach ($tools as $tool) {
                // $toolAttributes = $toolsAttributes->where('tool_id', $tool->id);
                // $formattedAttributes = [];

                // foreach ($toolAttributes as $attribute) {
                //     $values = $toolAttributeValues->where('tool_attribute_id', $attribute->id)->map(function ($value) {
                //         return [
                //             'id' => $value->id,
                //             'tool_attribute_id' => $value->tool_attribute_id,
                //             'value' => $value->value,
                //             'price' => $value->price,
                //         ];
                //     });

                //     $formattedAttributes[] = [
                //         'id' => $attribute->id,
                //         'tool_id' => $attribute->tool_id,
                //         'attribute_name_en' => $attribute->attribute_name_en,
                //         'attribute_name_ar' => $attribute->attribute_name_ar,
                //         'options_values' => $values->values(),  // This will re-index the array starting from 0
                //     ];
                // }

                $formattedTools[] = [
                    'id' => $tool->id,
                    'name_en' => $tool->name_en,
                    'name_ar' => $tool->name_ar,
                    'description_en' => $tool->description_en,
                    'description_ar' => $tool->description_ar,
                    // 'tool_attributes' => $formattedAttributes,
                    'tool_images' => $tool->toolImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
                        ];
                    }),
                ];
            }

            $activity->tools = $formattedTools;

            // User Image
            //             $image = DB::table('files')->find(138);
            // dd($image);

            $image = DB::table('files')->find($activity->serviceprovider->live_photo);
            // dd($activity->serviceprovider->live_photo); // = 138
            $activity->serviceprovider->profileimage = $image ? env('APP_URL') . 'storage/' . $image->name : null;

            // Company Image
            $image2 = DB::table('files')->find($activity->serviceprovider->company_logo);
            $activity->serviceprovider->companylogo = $image2 ? env('APP_URL') . 'storage/' . $image2->name : null;
        });

        return $activities;
    }

    // public function get_booking($perPage, $type): LengthAwarePaginator
    // {
    //     // DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");

    //     $activities = Activity::with('activityImages')
    //         ->select(
    //             'activities.id',
    //             'activities.user_id',
    //             'activity_type_id',
    //             'title_en',
    //             'title_ar',
    //             'description_en',
    //             'description_ar',
    //             'city_name_en',
    //             'city_name_ar',
    //             'duration',
    //             'start_date',
    //             'price',
    //             'capacity',
    //             'status',
    //             'lat',
    //             'long',
    //             'is_tourguideable',
    //             'is_photographer_available',
    //             'activity_days',
    //             'activity_times'
    //         )
    //         ->where('user_id', auth()->id())
    //         ->orderBy('activities.id', 'desc')
    //         ->where('status_id', $type)
    //         ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
    //         ->paginate($perPage);

    //     // Calculate average rating
    //     $activities->getCollection()->each(function ($activity) {
    //         $ratings = $activity->rate->pluck('rating');
    //         $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 1, '.', ',') : 0;
    //         // Add individual ratings with time_ago
    //         $activity->rate = $activity->rate->map(function ($rate) {
    //             return [
    //                 'rating' => $rate->rating,
    //                 'time_ago' => Carbon::parse($rate->created_at)->diffForHumans(),
    //                  $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans(),
    //             ];
    //         });

    //         // Process available_times for each activity
    //         $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
    //         $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

    //         $availableTimes = [];
    //         foreach ($activityDays as $index => $day) {
    //             if (isset($activityTimes[$index])) {
    //                 $date = Carbon::parse($day);
    //                 $dayName = $date->format('l');
    //                 $availableTimes[] = [
    //                     'date' => $day,
    //                     'day_name' => $dayName,
    //                     'time' => $activityTimes[$index],
    //                 ];
    //             }
    //         }
    //         $activity->available_times = $availableTimes;

    //         // Update image paths with the full URL
    //         $activity->activityImages->each(function ($image) {
    // $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path; // Fixed the path to /storage/
    //         });

    //         // Update tool image paths with the full URL
    //         $activity->tools->each(function ($tool) {
    //             $tool->toolImages->each(function ($image) {
    // $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path; // Fixed the path to /storage/
    //             });
    //         });
    //     });

    //     // $activities->each(function ($activity) {
    //     //     // Process available_times for each activity
    //     //    $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
    //         // $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

    //     //     $availableTimes = [];
    //     //     foreach ($activityDays as $index => $day) {
    //     //         if (isset($activityTimes[$index])) {
    //     //             $date = Carbon::parse($day); // Parse the date using Carbon
    //     //             $dayName = $date->format('l'); // Get the full day name (e.g., "Monday")
    //     //             $availableTimes[] = [
    //     //                 'date' => $day,
    //     //                 'day_name' => $dayName, // Add the day name
    //     //                 'time' => $activityTimes[$index],
    //     //             ];
    //     //         }
    //     //     }

    //     //     $activity->available_times = $availableTimes;

    //     //     // Optionally process tools and images
    //     // });

    //     // // Get the activity IDs for related data fetching
    //     // $activityIds = $activities->getCollection()->pluck('id')->toArray();

    //     // // Fetch related data in bulk to optimize the number of queries
    //     // $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //     // $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
    //     // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
    //     // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

    //     // // Attach related data to activities
    //     // $activities->getCollection()->each(function ($activity) use ($activityPlans, $activityTools, $toolsAttributes, $toolAttributeValues) {
    //     //     $activity->activity_plans = $activityPlans->where('activity_id', $activity->id);
    //     //     $tools = $activityTools->where('activity_id', $activity->id);

    //     //     // Add attributes to each tool
    //     //     $tools->each(function ($tool) use ($toolsAttributes, $toolAttributeValues) {
    //     //         $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
    //     //             $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id);
    //     //             return $att;
    //     //         });
    //     //         $tool->toolImages->each(function ($image) {
    //     //             $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path; // Fixed the path to /storage/
    //     //         });
    //     //     });
    //     //     $activity->tools = $tools;

    //     //     // Update image paths with the full URL
    //     //     $activity->activityImages->each(function ($image) {
    //     //         $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
    //     //     });
    //     // });

    //     return $activities;
    // }

    // Origin
    // public function get_booking($perPage, $type): LengthAwarePaginator
    // {
    //     // DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");

    //     $bookings = Booking::with(['activity.activityImages', 'booking_status', 'customer'])
    //         ->select('bookings.*')
    //         ->join('activities', 'activities.id', '=', 'bookings.activity_id')
    //         ->where('activities.user_id', auth()->id())
    //         ->where('bookings.status_id', $type)
    //         ->orderBy('bookings.id', 'desc')
    //         ->paginate($perPage);

    //     // Modify image paths correctly
    //     $bookings->getCollection()->each(function ($booking) {
    //         if ($booking->activity && $booking->activity->activityImages) {
    //             $booking->activity->activityImages->each(function ($image) {
    //                 // Avoid duplicating the base URL if it's already included
    //                 if (!str_starts_with($image->image_path, env('APP_URL'))) {
    //                     $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
    //                 }
    //             });
    //         }
    //     });

    //     return $bookings;
    // }
    public function get_booking($perPage, $type): LengthAwarePaginator
    {
        $todayDate = date('Y-m-d');
        $todayDay = strtolower(date('l'));

        $baseQuery = Booking::with([
            'activity.activityImages',
            'activity.activityType',
            'booking_status',
            'customer',
            'customer.userImage'
        ])
            ->select('bookings.*')
            ->join('activities', 'activities.id', '=', 'bookings.activity_id')
            ->where('activities.user_id', auth()->id());

        // 1 => Waiting , 2 => Rejected, 3 => Accepted , 4 => Cancelled by user , 5 => Paied , 6 => Cancelled by provider , 7 => Booking Completed , 8 => Archived

        // Filter based on type
        if ($type == 1) {  // Pending
            $baseQuery->where('bookings.status_id', $type);
            $baseQuery->orderBy('bookings.created_at', 'desc');
        } elseif ($type > 1) {  // Current
            $baseQuery
                ->orderBy('bookings.date', 'asc')
                ->where('bookings.status_id', '>', 1)
                ->where(function ($query) use ($todayDate, $todayDay) {
                    $query
                        ->where('bookings.date', '>=', $todayDate)
                        ->orWhere('bookings.date', $todayDay);
                });
        } elseif ($type == 0) {  // Archive
            $baseQuery->where(function ($query) use ($todayDate, $todayDay) {
                $query
                    // ->where('bookings.status_id', '=', 8)
                    ->where('bookings.date', '<', $todayDate);
                // ->where('bookings.date', '!=', $todayDay);
            });
        }

        // Execute pagination
        $bookings = $baseQuery->orderBy('bookings.id', 'desc')->paginate($perPage);

        // Prepare tool data
        $all_tool_ids = [];
        $tool_capacities = [];

        foreach ($bookings as $booking) {
            $toolIds = $booking->tool_id ? explode(',', $booking->tool_id) : [];
            $capacities = $booking->tool_capacity ? explode(',', $booking->tool_capacity) : [];

            foreach ($toolIds as $i => $id) {
                $id = (int) trim($id);
                $cap = (int) ($capacities[$i] ?? 0);
                $all_tool_ids[$id] = $cap;
            }
        }

        $selected_tools = CommercialTool::with('toolImages')
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

        // Add image paths for activity images
        $bookings->getCollection()->each(function ($booking) use ($selected_tools) {
            $toolIds = $booking->tool_id ? explode(',', $booking->tool_id) : [];

            $booking->selected_tools = $selected_tools->filter(function ($tool) use ($toolIds) {
                return in_array($tool->id, $toolIds);
            })->values();

            // Fix activity images path
            if ($booking->activity && $booking->activity->activityImages) {
                $booking->activity->activityImages->each(function ($image) {
                    if (!str_starts_with($image->image_path, env('APP_URL'))) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                    }
                });
            }

            if ($booking->customer && $booking->customer->relationLoaded('userImage')) {
                $booking->customer->user_image_url = $booking->customer->user_image_url;  // accessor
            }
        });

        return $bookings;
    }

    // public function filterActivities($filters, $perPage)
    // {
    //     $query = Activity::query()
    //         ->select(
    //             'activities.id',
    //             'activities.activity_type_id',
    //             'activities.title_en',
    //             'activities.title_ar',
    //             'activities.description_en',
    //             'activities.description_ar',
    //             'activities.city_name_en',
    //             'activities.city_name_ar',
    //             'activities.duration',
    //             'activities.start_date',
    //             'activities.price',
    //             'activities.capacity',
    //             'users.name',
    //             'activity_statuses.status',
    //             'activities.lat',
    //             'activities.long',
    //             'activities.is_tourguideable',
    //             'activities.is_photographer_available',
    //             'activities.activity_days',
    //             'activities.activity_times',
    //             // DB::raw('AVG(ratings.rating) as average_rating') // Average only when ratings exist
    //             DB::raw('IFNULL(AVG(ratings.rating), 0) as average_rating') // Handle nulls safely
    //             // DB::raw('(CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')

    //         );

    //     // Dynamically add the `favourite` column if the user is authenticated
    //     if (auth()->check()) {
    //         $query->addSelect(
    //             DB::raw('(CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
    //         );
    //     }

    //     // Add filters
    //     if (!empty($filters['title'])) {
    //         $query->whereRaw('LOWER(title_en) LIKE ?', ['%' . strtolower($filters['title']) . '%']);
    //         $query->whereRaw('LOWER(title_ar) LIKE ?', ['%' . strtolower($filters['title']) . '%']);
    //     }
    //     if (!empty($filters['location'])) {
    //         $query->whereRaw('LOWER(city_name_en) LIKE ?', ['%' . strtolower($filters['location']) . '%'])
    //             ->orWhereRaw('LOWER(city_name_ar) LIKE ?', ['%' . strtolower($filters['location']) . '%']);
    //     }

    //     // if (!empty($filters['price'])) {
    //     //     $query->where('price', '=', $filters['price']);
    //     // }
    //     if (!empty($filters['duration'])) {
    //         $query->where('duration', '=', $filters['duration']);
    //     }

    //     if (!empty($filters['price_from'])) {
    //         $query->where('activities.price', '>=', $filters['price_from']);
    //     }
    //     if (!empty($filters['price_to'])) {
    //         $query->where('activities.price', '<=', $filters['price_to']);
    //     }

    //     if (!empty($filters['date_from'])) {
    //         $query->whereDate('activities.created_at', '>=', $filters['date_from']);
    //     }
    //     if (!empty($filters['date_to'])) {
    //         $query->whereDate('activities.created_at', '<=', $filters['date_to']);
    //     }

    //     // $query->havingRaw('AVG(ratings.rating) = ?', [$filters['rate']]);
    //     if (!empty($filters['rate'])) {
    //         $query->havingRaw('AVG(ratings.rating) = ?', [$filters['rate']]);
    //     }

    //     // Apply joins
    //     $query->leftJoin('ratings', 'ratings.activity_id', '=', 'activities.id') // Use LEFT JOIN to handle missing ratings
    //         ->join('users', 'activities.user_id', '=', 'users.id')
    //         ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id');

    //     // If the user is logged in, join the wishlists table
    //     if (auth()->check()) {
    //         $query->leftJoin('wishlists', function ($join) {
    //             $join->on('wishlists.activity_id', '=', 'activities.id')
    //                 ->where('wishlists.user_id', '=', auth()->id());
    //         });
    //     }

    //     $query->groupBy(
    //         'activities.id',
    //         'activities.activity_type_id',
    //         'activities.title_en',
    //         'activities.title_ar',
    //         'activities.description_en',
    //         'activities.description_ar',
    //         'activities.city_name_en',
    //         'activities.city_name_ar',
    //         'activities.duration',
    //         'activities.start_date',
    //         'activities.price',
    //         'activities.capacity',
    //         'users.name',
    //         'activity_statuses.status',
    //         'activities.lat',
    //         'activities.long',
    //         'activities.is_tourguideable',
    //         'activities.is_photographer_available',
    //         'activities.activity_days',
    //         'activities.activity_times'
    //     )
    //         ->where('status_id', 3)
    //         ->orderBy('activities.id', 'desc');

    //     // Paginate the query results
    //     $activities = $query->paginate($perPage);

    //     $activities->each(function ($activity) {
    //         // Process available_times for each activity
    //         $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
    // $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

    //         $availableTimes = [];
    //         foreach ($activityDays as $index => $day) {
    //             if (isset($activityTimes[$index])) {
    //                 $date = Carbon::parse($day); // Parse the date using Carbon
    //                 $dayName = $date->format('l'); // Get the full day name (e.g., "Monday")
    //                 $availableTimes[] = [
    //                     'date' => $day,
    //                     'day_name' => $dayName, // Add the day name
    //                     'time' => $activityTimes[$index],
    //                 ];
    //             }
    //         }

    //         $activity->available_times = $availableTimes;

    //         // Optionally process tools and images
    //     });

    //     // Fetch related data for optimization
    //     $activityIds = $activities->getCollection()->pluck('id')->toArray();
    //     $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //     $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
    //     $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
    //     $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

    //     // Attach related data to activities
    //     $activities->getCollection()->each(function ($activity) use ($activityPlans, $activityTools, $toolsAttributes, $toolAttributeValues) {
    //         $activity->activity_plans = $activityPlans->where('activity_id', $activity->id);
    //         $tools = $activityTools->where('activity_id', $activity->id);

    //         $tools->each(function ($tool) use ($toolsAttributes, $toolAttributeValues) {
    //             $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
    //                 $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id);
    //                 return $att;
    //             });
    //             $tool->toolImages->each(function ($image) {
    //                 $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path; // Fixed the path to /storage/
    //             });
    //         });

    //         $activity->tools = $tools;

    //         // Update image paths
    //         $activity->activityImages->each(function ($image) {
    //             $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
    //         });
    //     });

    //     return $activities;
    // }

    public function filterActivities($filters, $perPage)
    {
        $userId = auth()->id();

        $from = Carbon::today();
        $to = Carbon::now()->addMonths(3);

        $query = Activity::with(['activityType'])->select(
            'activities.id',
            'activities.user_id',
            'activities.activity_type_id',
            'activities.title_en',
            'activities.title_ar',
            'activities.at_home',
            'activities.description_en',
            'activities.description_ar',
            'activities.city_name_en',
            'activities.city_name_ar',
            'activities.country_name_en',
            'activities.country_name_ar',
            'activities.duration',
            'activities.plan_activity',
            'activities.start_date',
            'activities.price',
            'activities.spoken_lang',
            'activities.capacity',
            'users.name',
            'activity_statuses.status',
            'activities.lat',
            'activities.long',
            'activities.is_tourguideable',
            'activities.tourguide_price',
            'activities.is_photographer_available',
            'activities.photographer_price',
            'activities.activity_days',
            'activities.activity_times_start',
            'activities.activity_times_end',
            'activities.activity_single_dates',
            DB::raw('IFNULL(AVG(ratings.rating), 0) as average_rating')
        );

        // Dynamically add the `favourite` column if the user is authenticated
        // if (auth()->check()) {
        $query->addSelect(
            DB::raw('(CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
        );
        // }

        // dd($filters);

        // Add filters
        if (!empty($filters['title'])) {
            $query->whereRaw('LOWER(title_en) LIKE ?', ['%' . strtolower($filters['title']) . '%']);
            $query->whereRaw('LOWER(title_ar) LIKE ?', ['%' . strtolower($filters['title']) . '%']);
        }
        if (!empty($filters['city'])) {
            $query
                ->whereRaw('LOWER(city_name_en) LIKE ?', ['%' . strtolower($filters['city']) . '%'])
                ->orWhereRaw('LOWER(city_name_ar) LIKE ?', ['%' . strtolower($filters['city']) . '%']);
        }
        if (!empty($filters['country'])) {
            $query
                ->whereRaw('LOWER(country_name_en) LIKE ?', ['%' . strtolower($filters['country']) . '%'])
                ->orWhereRaw('LOWER(country_name_ar) LIKE ?', ['%' . strtolower($filters['country']) . '%']);
        }
        if (!empty($filters['plan'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereRaw('LOWER(plan_activity) LIKE ?', ['%' . strtolower($filters['plan']) . '%']);
            });
        }

        if (!empty($filters['duration'])) {
            $query->where('duration', '=', $filters['duration']);
        }
        if (!empty($filters['price_from'])) {
            $query->where('activities.price', '>=', $filters['price_from']);
        }
        if (!empty($filters['price_to'])) {
            $query->where('activities.price', '<=', $filters['price_to']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('activities.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('activities.created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['rate'])) {
            $query->havingRaw('AVG(ratings.rating) = ?', [$filters['rate']]);
        }
        if (!empty($filters['type_id'])) {
            if (is_array($filters['type_id'])) {
                $query->whereIn('activities.activity_type_id', $filters['type_id']);
            } else {
                $query->where('activities.activity_type_id', '=', $filters['type_id']);
            }
        }
        if (!empty($filters['plan']) && $filters['plan'] === 'no') {
            $query
                ->where('plan_activity', 'no')
                ->whereNotNull('activity_single_dates')
                ->where(function ($q) use ($from, $to) {
                    $period = CarbonPeriod::create(
                        $from->copy()->startOfDay(),
                        $to->copy()->endOfDay()
                    );

                    foreach ($period as $date) {
                        $q->orWhere(
                            'activity_single_dates',
                            'LIKE',
                            '%' . $date->toDateString() . '%'
                        );
                    }
                });
        }

        if (!empty($filters['plan']) && $filters['plan'] == 'yes') {
            $query->where(function ($q) use ($from, $to) {
                $q
                    ->where('plan_activity', 'yes')
                    ->whereBetween('start_date', [
                        $from->toDateString(),
                        $to->toDateString()
                    ]);
            });
        }

        // Apply joins
        $query
            ->leftJoin('ratings', 'ratings.activity_id', '=', 'activities.id')
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id');

        // If the user is logged in, join the wishlists table
        // if (auth()->check()) {
        $query->leftJoin('wishlists', function ($join) {
            $join
                ->on('wishlists.activity_id', '=', 'activities.id')
                ->where('wishlists.user_id', '=', auth()->id());
        });
        // }

        $query
            ->where('status_id', 1)
            ->groupBy(
                'activities.id',
                'activities.user_id',
                'activities.activity_type_id',
                'activities.title_en',
                'activities.title_ar',
                'activities.at_home',
                'activities.description_en',
                'activities.description_ar',
                'activities.city_name_en',
                'activities.city_name_ar',
                'activities.country_name_en',
                'activities.country_name_ar',
                'activities.duration',
                'activities.plan_activity',
                'activities.start_date',
                'activities.price',
                'activities.spoken_lang',
                'activities.capacity',
                'users.name',
                'activity_statuses.status',
                'activities.lat',
                'activities.long',
                'activities.is_tourguideable',
                'activities.tourguide_price',
                'activities.is_photographer_available',
                'activities.photographer_price',
                'activities.activity_days',
                'activities.activity_times_start',
                'activities.activity_times_end',
                'activities.activity_single_dates',
                'wishlists.activity_id'
            )
            ->orderBy('activities.id', 'desc');

        // Paginate the query results
        $activities = $query->paginate($perPage);
        // Calculate average rating
        $activities->getCollection()->transform(function ($activity) use ($userId) {
            // dd($userId);

            $activity->sameUsersameProvider = ($activity->user_id == $userId) ? 'yes' : 'no';

            $ratings = $activity->rate->pluck('rating');  // Assuming 'rate' is a relationship with a 'rating' field
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');
            // Add individual ratings with time_ago
            $activity->rate = $activity->rate->map(function ($rate) {
                return [
                    'rating' => $rate->rating,
                    // 'time_ago' => Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans(),
                ];
            });

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

            // $activity->service_provider = $activity->user->where('id', $activity->user_id)->first();
            $activity->service_provider = User::where('id', $activity->user_id)->first();

            return $activity;
        });

        // Process each activity
        // $activities->each(function ($activity) {
        //     // Process available_times for each activity
        //  $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
        // $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

        //     $availableTimes = [];
        //     foreach ($activityDays as $index => $day) {
        //         if (isset($activityTimes[$index])) {
        //             $date = Carbon::parse($day);
        //             $dayName = $date->format('l');
        //             $availableTimes[] = [
        //                 'date' => $day,
        //                 'day_name' => $dayName,
        //                 'time' => $activityTimes[$index],
        //             ];
        //         }
        //     }
        //     $activity->available_times = $availableTimes;
        // });

        // Fetch related data for optimization
        $activityIds = $activities->getCollection()->pluck('id')->toArray();
        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Attach related data to activities
        $activities->getCollection()->each(function ($activity) use ($activityPlans, $activityTools /* , $toolsAttributes, $toolAttributeValues */) {
            // Attach activity plans
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

            // Attach tools with nested attributes and images
            $tools = $activityTools->where('activity_id', $activity->id);
            $formattedTools = [];

            foreach ($tools as $tool) {
                // $toolAttributes = $toolsAttributes->where('tool_id', $tool->id);
                // $formattedAttributes = [];

                // foreach ($toolAttributes as $attribute) {
                //     $values = $toolAttributeValues->where('tool_attribute_id', $attribute->id)->map(function ($value) {
                //         return [
                //             'id' => $value->id,
                //             'tool_attribute_id' => $value->tool_attribute_id,
                //             'value' => $value->value,
                //             'price' => $value->price,
                //         ];
                //     });

                // $formattedAttributes[] = [
                //     'id' => $attribute->id,
                //     'tool_id' => $attribute->tool_id,
                //     'attribute_name_en' => $attribute->attribute_name_en,
                //     'attribute_name_ar' => $attribute->attribute_name_ar,
                //     'options_values' => $values,
                // ];
                // }

                $formattedTools[] = [
                    'id' => $tool->id,
                    'name_en' => $tool->name_en,
                    'name_ar' => $tool->name_ar,
                    'description_en' => $tool->description_en,
                    'description_ar' => $tool->description_ar,
                    // 'tool_attributes' => $formattedAttributes,
                    'tool_images' => $tool->toolImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
                        ];
                    }),
                ];
            }

            $activity->tools = $formattedTools;

            // Update image paths
            $activity->activityImages->each(function ($image) {
                $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
            });
        });

        return $activities;
    }

    public function filterActivities_type($filters, $perPage)
    {
        // Base query
        $query = Activity::with('activityType')
            ->select(
                'activities.id',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'at_home',
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
                'name',
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
                'activity_single_dates'
            )
            // ->where('activities.id', 38) // For Test
            ->where('status_id', 1)
            ->join('users', 'activities.user_id', '=', 'users.id')
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->orderBy('activities.id', 'desc');

        $query->addSelect(
            DB::raw('(CASE WHEN wishlists.activity_id IS NOT NULL THEN 1 ELSE 0 END) as is_favourite')
        );

        $query->leftJoin('wishlists', function ($join) {
            $join
                ->on('wishlists.activity_id', '=', 'activities.id')
                ->where('wishlists.user_id', '=', auth()->id());
        });

        // Apply filters
        if (!empty($filters['activity_type_id'])) {
            $query->where('activity_type_id', $filters['activity_type_id']);
        }

        // Log the SQL query for debugging
        // Log::info('Filter Activities SQL Query', [
        //     'query' => $query->toSql(),
        //     'bindings' => $query->getBindings(),
        // ]);

        $query->where(function ($q) {
            $q
                ->where('plan_activity', '!=', 'yes')
                ->orWhere(function ($q2) {
                    $q2
                        ->where('plan_activity', 'yes')
                        ->where('start_date', '>=', date('Y-m-d'));
                });
        });

        // Paginate results
        $activities = $query->paginate($perPage);

        // Calculate average rating
        $activities->getCollection()->transform(function ($activity) {
            $ratings = $activity->rate->pluck('rating');  // Assuming 'rate' is a relationship with a 'rating' field
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

            // Add individual ratings with time_ago
            $activity->rate = $activity->rate->map(function ($rate) {
                return [
                    'rating' => $rate->rating,
                    // 'time_ago' => Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans(),
                ];
            });

            // Process available_times for each activity
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
            $activity->service_provider = User::where('id', $activity->user_id)->first();
            return $activity;
        });

        // Check if no results are found
        if ($activities->isEmpty()) {
            return response()->json([
                'message' => 'No activities found for the given filters',
                'message_ar' => 'لم يتم العثور على رحلات للمرشحات المحددة',
            ], 404);
        }

        // Collect activity IDs for related data fetching
        $activityIds = $activities->getCollection()->pluck('id')->toArray();

        // Fetch related data efficiently
        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Attach related data to activities
        $activities->getCollection()->transform(function ($activity) use ($activityPlans, $activityTools /*, $toolsAttributes, $toolAttributeValues*/) {
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();  // Ensure it's an indexed array

            $tools = $activityTools->where('activity_id', $activity->id);
            $tools->transform(function ($tool) /*use ($toolsAttributes, $toolAttributeValues)*/ {
                // Convert tool_attributes to an indexed array
                // $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
                //     $att->options_values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values();  // Ensure values is an indexed array
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
            // if ($activity->relationLoaded('activityImages')) {
            //     $activity->activityImages->transform(function ($image) {
            //         $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
            //         return $image;
            //     });
            // }
            // Update image paths
            $activity->activityImages->each(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
            });

            return $activity;
        });

        return $activities;
    }

    public function create(array $data)
    {
        $activity = new Activity;

        DB::transaction(function () use ($data, &$activity) {
            if ($data['duration'] > 1) {
                $plan_activity_value = 'yes';
            } else {
                $plan_activity_value = 'no';
            }
            // Prepare data for activities table
            $activityData = [
                'user_id' => $data['user_id'],
                'title_ar' => $data['title_ar'],
                'description_en' => $data['description_en'],
                'description_ar' => $data['description_ar'],
                'city_name_en' => $data['city_name_en'],
                'city_name_ar' => $data['city_name_ar'],
                'country_name_en' => $data['country_name_en'],
                'country_name_ar' => $data['country_name_ar'],
                'duration' => $data['duration'],
                'plan_activity' => $plan_activity_value,
                'price' => $data['price'],
                'spoken_lang' => $data['spoken_lang'],
                'capacity' => $data['capacity'],
                'status_id' => $data['status_id'],
                'lat' => $data['lat'],
                'long' => $data['long'],
                'title_en' => $data['title_en'],
                'activity_type_id' => $data['activity_type_id'],
                'is_tourguideable' => $data['is_tourguideable'],  // Assuming this field exists
                'tourguide_price' => $data['tourguide_price'],
                'is_photographer_available' => $data['is_photographer_available'],
                'photographer_price' => $data['photographer_price'],
                'privacy_policy_en' => $data['privacy_policy_en'],
                'privacy_policy_ar' => $data['privacy_policy_ar'],
                'cancel_policy_en' => $data['cancel_policy_en'],
                'cancel_policy_ar' => $data['cancel_policy_ar'],
            ];
            if (!empty($data['activity_days'])) {
                $activityData['activity_days'] = implode(',', $data['activity_days']);
                $activityData['activity_times_start'] = implode(',', $data['activity_times_start']);
                $activityData['activity_times_end'] = implode(',', $data['activity_times_end']);
            } else {
                $activityData['activity_days'] = '';
                $activityData['activity_times_start'] = '';
                $activityData['activity_times_end'] = '';
            }
            // Insert activity and get the activity id
            $activity = Activity::create($activityData);

            // Prepare data for activity plans
            if (empty($data['activity_days'])) {
                $activityPlansData = [];
                foreach ($data['activity_plans'] as $plan) {
                    $activityPlansData[] = [
                        'activity_id' => $activity->id,
                        'city_name_en' => $plan['city_name_en'],
                        'city_name_ar' => $plan['city_name_ar'],
                        'starts_at' => $plan['starts_at'],
                        'ends_at' => $plan['ends_at'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                // Bulk insert activity plans
                ActivityPlan::insert($activityPlansData);
            }

            // Prepare data for tools
            foreach ($data['tools'] as $tool) {
                $toolModel = ActivityTool::create([
                    'name_en' => $tool['name_en'],
                    'name_ar' => $tool['name_ar'],
                    'price' => $tool['tool_price'],
                    'description_en' => $tool['description_en'],
                    'description_ar' => $tool['description_ar'],
                    'activity_id' => $activity->id,
                ]);

                $toolAttribute = ToolAttribute::create([
                    'tool_id' => $toolModel->id,
                    'attribute_name_en' => $tool['tool_attributes']['attribute_name_en'],
                    'attribute_name_ar' => $tool['tool_attributes']['attribute_name_ar'],
                ]);

                // Prepare and insert tool attribute values
                $values = [];
                foreach ($tool['tool_attributes']['values'] as $value) {
                    $values[] = [
                        'tool_attribute_id' => $toolAttribute->id,
                        'value' => $value['value'],
                        'price' => $value['price'],
                        // 'terms_conditions' => $value['termsConditions'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                ToolAttributeValues::insert($values);
            }
        });

        return $activity;
    }

    public function update($activity, array $data)
    {
        $activity->update($data);
        return $activity;
    }

    public function delete($activity)
    {
        return $activity->delete();
    }

    public function findForServiceProvider($id, $serviceProviderId)
    {
        $query = Activity::query()
            ->select(
                'activities.id',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'at_home',
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
                'lat',
                'long',
                'is_tourguideable',
                'tourguide_price',
                'is_photographer_available',
                'photographer_price',
                'capacity',
                'status',
                'activity_days',
                'privacy_policy_en',
                'privacy_policy_ar',
                'cancel_policy_en',
                'cancel_policy_ar',
                'activity_times_start',
                'activity_times_end',
                'activity_single_dates'
            )
            ->where('user_id', $serviceProviderId)
            ->where('activities.id', $id)
            ->join('activity_statuses', 'activity_statuses.id', 'activities.status_id');
        $activities = $query->get();

        // Calculate average rating
        $ratings = $activities->rate->pluck('rating');  // Assuming 'rate' has a 'rating' field
        $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0;  // Calculate average or default to 0
        // $activities->average_rating = number_format($averageRating, 1, '.', ','); // Add average rating to the activity response add show only one number after point
        $activities->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

        $activities->rate = $activities->rate->map(function ($rate) {
            // $rate->name = $rate->user ? $rate->user->name : null; // Add user name
            // $rate->user_email = $rate->user ? $rate->user->email : null; // Add user email
            $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans();  // Add time_ago
            $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
            return $rate;
        });

        $activityIds = $activities->pluck('id')->toArray();
        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValue = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();
        $activities->getCollection()->transform(function ($activity) use ($activityPlans, $activityTools /*, $toolsAttributes, $toolAttributeValue*/) {
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();
            $tools = $activityTools->where('activity_id', $activity->id);
            // $toolOfToolAttributes = $toolsAttributes->whereIn('tool_id', $tools->pluck('id')->toArray());
            // $toolOfToolAttributes->transform(function ($att) use ($toolAttributeValue) {
                // $att->values = $toolAttributeValue->where('tool_attribute_id', $att->id)->values();
                // return $att;
            // });
            // $tools->transform(function ($tool) use ($toolOfToolAttributes) {
            //     $tool->tool_attributes = $toolOfToolAttributes->where('tool_id', $tool->id)->values();
            //     return $tool;
            // });
            $tools->toolImages->each(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Fixed the path to /storage/
            });
            $activity->tools = $tools->values();

            $activity->service_provider = $activity->user->where('id', $activity->user_id)->first();
            return $activity;
        });

        return $activities->first();
    }

    // public function find_old($id)
    // {
    //     $query = Activity::query()
    //         ->select(
    //             'title_en',
    //             'title_ar',
    //             'description_en',
    //             'description_ar',
    //             'city_name_en',
    //             'city_name_ar',
    //             'duration',
    //             'price',
    //             'capacity',
    //             'activities.id',
    //             'status',
    //             'activity_days'
    //         )
    //         ->where('activities.id', $id)
    //         ->join('activity_statuses', 'activity_statuses.id', 'activities.status_id');
    //     $activities = $query->get();
    //     $activityIds = $activities->pluck('id')->toArray();
    //     $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //     $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
    //     $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
    //     $toolAttributeValue = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();
    //     $activities->transform(function ($activity) use ($activityPlans, $activityTools, $toolsAttributes, $toolAttributeValue) {
    //         $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();
    //         $tools = $activityTools->where('activity_id', $activity->id);
    //         $toolOfToolAttributes = $toolsAttributes->whereIn('tool_id', $tools->pluck('id')->toArray());
    //         $toolOfToolAttributes->transform(function ($att) use ($toolAttributeValue) {
    //             $att->values = $toolAttributeValue->where('tool_attribute_id', $att->id)->values();
    //             return $att;
    //         });
    //         $tools->transform(function ($tool) use ($toolOfToolAttributes) {
    //             $tool->tool_attributes = $toolOfToolAttributes->where('tool_id', $tool->id)->values();
    //             return $tool;
    //         });
    //         $activity->tools = $tools->values();
    //         return $activity;
    //     });

    //     return $activities->first();
    // }

    public function findss($id)
    {
        // Fetch the activity details
        $query = Activity::query()
            ->select(
                'activities.id',
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
                'start_date',
                'price',
                'activities.spoken_lang',
                'capacity',
                'status',
                'lat',
                'long',
                'activity_days',
                'privacy_policy_en',
                'privacy_policy_ar',
                'cancel_policy_en',
                'cancel_policy_ar',
                'activity_times_start',
                'activity_times_end',
                'activity_single_dates'
            )
            ->where('activities.id', $id)
            ->join('activity_statuses', 'activity_statuses.id', 'activities.status_id');

        $activities = $query->get();

        if ($activities->isEmpty()) {
            return null;  // Return null if no activity is found
        }

        // Extract activity IDs
        $activityIds = $activities->pluck('id')->toArray();

        // Load related data
        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
        // $toolAttributeValue = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

        // Process and transform the activities
        $activities->transform(function ($activity) use ($activityPlans, $activityTools /*, $toolsAttributes, $toolAttributeValue*/) {
            $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

            $tools = $activityTools->where('activity_id', $activity->id);
            // $toolOfToolAttributes = $toolsAttributes->whereIn('tool_id', $tools->pluck('id')->toArray());

            // Attach attribute values to attributes
            // $toolOfToolAttributes->transform(function ($attribute) use ($toolAttributeValue) {
            //     $attribute->values = $toolAttributeValue->where('tool_attribute_id', $attribute->id)->values();
            //     return $attribute;
            // });

            // Attach attributes to tools
            // $tools->transform(function ($tool) use ($toolOfToolAttributes) {
            //     $tool->tool_attributes = $toolOfToolAttributes->where('tool_id', $tool->id)->values();
            //     return $tool;
            // });
            $tools->toolImages->each(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Fixed the path to /storage/
            });

            $activity->tools = $tools->values();

            // Update image paths with the full URL
            if ($activity->activityImages) {
                $activity->activityImages->each(function ($image) {
                    $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;  // Corrected path
                });
            }

            return $activity;
        });

        return $activities->first();
    }

    // public function find($id)
    // {
    //     try {
    //         // Fetch the activity with related data
    //         $activity = Activity::with(['activityImages', 'activityType', 'serviceprovider'])
    //             ->select(
    //                 'activities.id',
    //                 'activities.user_id',
    //                 'activity_type_id',
    //                 'title_en',
    //                 'title_ar',
    //                 'description_en',
    //                 'description_ar',
    //                 'city_name_en',
    //                 'city_name_ar',
    //                 'duration',
    //                 'plan_activity',
    //                 'start_date',
    //                 'price',
    //                 'capacity',
    //                 'status',
    //                 'lat',
    //                 'long',
    //                 'is_tourguideable',
    //                 'tourguide_price',
    //                 'is_photographer_available',
    //                 'photographer_price',
    //                 'activity_days',
    //                 'activity_times',
    //                 'privacy_policy_en',
    //                 'privacy_policy_ar',
    //                 'cancel_policy_en',
    //                 'cancel_policy_ar',
    //             )
    //             // ->where('activities.id', $id)
    //             ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
    //             ->where('activities.id', $id)
    //             ->firstOrFail();

    //         // Calculate average rating
    //         $ratings = $activity->rate->pluck('rating'); // Assuming 'rate' has a 'rating' field
    //         $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0; // Calculate average or default to 0
    //         // $activity->average_rating = number_format($averageRating, 1, '.', ','); // Add average rating to the activity response add show only one number after point
    //         $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

    //         $activity->rate = $activity->rate->map(function ($rate) {
    //             // $rate->name = $rate->user ? $rate->user->name : null; // Add user name
    //             // $rate->user_email = $rate->user ? $rate->user->email : null; // Add user email
    //             $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans(); // Add time_ago
    //             $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
    //             return $rate;
    //         });

    //         // Process available_times for the activity
    //         $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
    //         $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

    //         $availableTimes = [];
    //         foreach ($activityDays as $index => $day) {
    //             if (isset($activityTimes[$index])) {
    //                 $date = Carbon::parse($day); // Parse the date to get the day name
    //                 $dayName = $date->format('l'); // Get the full day name (e.g., "Monday")
    //                 $availableTimes[] = [
    //                     'day_name' => $dayName, // Include only the day name
    //                     'time' => $activityTimes[$index], // Include the time
    //                 ];
    //             }
    //         }
    //         $activity->available_times = $availableTimes;

    //         // Fetch related data in bulk to optimize the number of queries
    //         $activityIds = [$activity->id]; // Since this is a single activity, we only need its ID

    //         $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //         $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
    //         $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
    //         $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

    //         // Attach related data to the activity
    //         $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values(); // Ensure it's an indexed array

    //         $tools = $activityTools->where('activity_id', $activity->id);
    //         $tools->transform(function ($tool) use ($toolsAttributes, $toolAttributeValues) {
    //             // Convert tool_attributes to an indexed array
    //             $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
    //                 $att->options_values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values(); // Ensure values is an indexed array
    //                 return $att;
    //             })->values(); // Ensure tool_attributes is an indexed array

    //             // Convert tool_images to an indexed array
    //             $tool->tool_images = $tool->toolImages->map(function ($image) {
    //                 $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
    //                 return $image;
    //             })->values(); // Ensure tool_images is an indexed array

    //             return $tool;
    //         });

    //         $activity->tools = $tools->values(); // Ensure tools is an indexed array

    //         // Attach images with full paths
    //         if ($activity->relationLoaded('activityImages')) {
    //             $activity->activityImages->transform(function ($image) {
    //                 $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
    //                 return $image;
    //             });
    //         }

    //         // return response()->json(['data' => $activity], 200);

    //          return response()->json([
    //             'message' => 'success',
    //             'data' => $activity
    //         ], 404);

    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         return response()->json([
    //             'message' => 'Activity not found',
    //             'message_ar' => 'لم يتم العثور على الرحلة',
    //         ], 404);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while processing your request.',
    //             'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
    //         ], 500);
    //     }
    // }
    // public function find($id)
    // {
    //     try {
    //         // Fetch the activity with related data
    //         $activity = Activity::with('activityImages')
    //             ->select(
    //                 'activities.id',
    //                 'activities.user_id',
    //                 'activity_type_id',
    //                 'title_en',
    //                 'title_ar',
    //                 'description_en',
    //                 'description_ar',
    //                 'city_name_en',
    //                 'city_name_ar',
    //                 'duration',
    //                 'plan_activity',
    //                 'start_date',
    //                 'price',
    //                 'capacity',
    //                 'status',
    //                 'lat',
    //                 'long',
    //                 'is_tourguideable',
    //                 'tourguide_price',
    //                 'is_photographer_available',
    //                 'photographer_price',
    //                 'activity_days',
    //                 'activity_times',

    //                 'privacy_policy_en',
    //                 'privacy_policy_ar',
    //                 'cancel_policy_en',
    //                 'cancel_policy_ar',

    //             )
    //             // ->where('user_id', auth()->id())
    //             ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
    //             ->where('activities.id', $id)
    //             ->firstOrFail();

    //         // Calculate average rating
    //         $ratings = $activity->rate->pluck('rating'); // Assuming 'rate' has a 'rating' field
    //         $averageRating = $ratings->isNotEmpty() ? $ratings->average() : 0; // Calculate average or default to 0
    //         // $activity->average_rating = number_format($averageRating, 1, '.', ','); // Add average rating to the activity response add show only one number after point
    //         $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

    //         $activity->rate = $activity->rate->map(function ($rate) {
    //             // $rate->name = $rate->user ? $rate->user->name : null; // Add user name
    //             // $rate->user_email = $rate->user ? $rate->user->email : null; // Add user email
    //             $rate->time_ago = \Carbon\Carbon::parse($rate->created_at)->diffForHumans(); // Add time_ago
    //             $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans();
    //             return $rate;
    //         });

    //         // Process available_times for the activity
    //         $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
    //         $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

    //         $availableTimes = [];
    //         foreach ($activityDays as $index => $day) {
    //             if (isset($activityTimes[$index])) {
    //                 $date = Carbon::parse($day); // Parse the date to get the day name
    //                 $dayName = $date->format('l'); // Get the full day name (e.g., "Monday")
    //                 $availableTimes[] = [
    //                     'day_name' => $dayName, // Include only the day name
    //                     'time' => $activityTimes[$index], // Include the time
    //                 ];
    //             }
    //         }
    //         $activity->available_times = $availableTimes;

    //         // Fetch related data in bulk to optimize the number of queries
    //         $activityIds = [$activity->id]; // Since this is a single activity, we only need its ID

    //         $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //         $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
    //         $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
    //         $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

    //         // Attach related data to the activity
    //         $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values(); // Ensure it's an indexed array

    //         $tools = $activityTools->where('activity_id', $activity->id);
    //         $tools->transform(function ($tool) use ($toolsAttributes, $toolAttributeValues) {
    //             // Convert tool_attributes to an indexed array
    //             $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
    //                 $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values(); // Ensure values is an indexed array
    //                 return $att;
    //             })->values(); // Ensure tool_attributes is an indexed array

    //             // Convert tool_images to an indexed array
    //             $tool->tool_images = $tool->toolImages->map(function ($image) {
    //                 $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
    //                 return $image;
    //             })->values(); // Ensure tool_images is an indexed array

    //             return $tool;
    //         });

    //         $activity->tools = $tools->values(); // Ensure tools is an indexed array

    //         // Attach images with full paths
    //         if ($activity->relationLoaded('activityImages')) {
    //             $activity->activityImages->transform(function ($image) {
    //                 $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
    //                 return $image;
    //             });
    //         }

    //         return response()->json([
    //             'data' => $activity
    //         ], 200);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         return response()->json([
    //             'message' => 'Activity not found',
    //             'message_ar' => 'لم يتم العثور على الرحلة'
    //         ], 404);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An unexpected error occurred',
    //             'message_ar' => 'حدث خطأ غير متوقع'
    //         ], 500);
    //     }
    // }

    public function addORdeletewishlist(array $data)
    {
        DB::beginTransaction();
        try {
            // Check if the user already exists in the waiting list
            $existingActivity = Activity::where('id', $data['activity_id'])
                ->first();

            $existingActivityinwishlist = Wishlist::where('activity_id', $data['activity_id'])
                ->where('user_id', auth()->id())
                ->first();

            if ($existingActivity && !$existingActivityinwishlist) {
                // Add new record if the user doesn't exist
                $wishList = Wishlist::create([
                    'user_id' => auth()->id(),
                    'activity_id' => $data['activity_id'],
                    'type' => 'activity',
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'Added to Wish list successfully.',
                    'message_ar' => 'تمت الإضافة إلى قائمة الرغبات بنجاح.',
                    'data' => $wishList,
                ], 201);
            } else {
                $wishListdeleted = Wishlist::where('activity_id', $data['activity_id'])->where('user_id', auth()->id())->delete();

                DB::commit();
                return response()->json([
                    'message' => 'Deleted from wish list successfully.',
                    'message_ar' => 'تم الحذف من قائمة الرغبات بنجاح.',
                    // 'data' => $wishListdeleted,
                ], 204);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_wishlist($perPage): LengthAwarePaginator
    {
        $userId = auth()->id();
        // Eager load relationships to avoid N+1 queries
        $activities = Activity::with(['activityImages'])
            ->select(
                'activities.id',
                'activities.user_id',
                'activity_type_id',
                'title_en',
                'title_ar',
                'at_home',
                'description_en',
                'description_ar',
                'city_name_en',
                'city_name_ar',
                'country_name_en',
                'country_name_ar',
                'duration',
                'price',
                'activities.spoken_lang',
                'plan_activity',
                'start_date',
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
                'activity_times_start',
                'activity_times_end',
                'activity_single_dates'
            )
            ->where('wishlists.user_id', auth()->id())
            ->orderBy('activities.id', 'desc')
            ->where('type', 'activity')
            ->where(function ($q) {
                $q
                    ->where('plan_activity', '!=', 'yes')
                    ->orWhere(function ($q2) {
                        $q2
                            ->where('plan_activity', 'yes')
                            ->where('start_date', '>=', date('Y-m-d'));
                    });
            })
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->join('wishlists', 'wishlists.activity_id', '=', 'activities.id')
            ->paginate($perPage);

        // Calculate average rating
        $activities->getCollection()->each(function ($activity) use ($userId) {
            $activity->sameUsersameProvider = ($activity->user_id == $userId) ? 'yes' : 'no';
            $activityIds = $activity->pluck('id')->toArray();
            $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
            $activityTools = ActivityTool::whereIn('activity_id', $activityIds)->get();
            // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
            // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

            $ratings = $activity->rate->pluck('rating');
            // $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 1, '.', ',') : 0;
            $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 4, '.', ',') : number_format(0, 4, '.', ',');

            // Add individual ratings with time_ago
            $activity->rate = $activity->rate->map(function ($rate) {
                return [
                    'rating' => $rate->rating,
                    // 'time_ago' => Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago = Carbon::parse($rate->created_at)->diffForHumans(),
                    $rate->time_ago_ar = \Carbon\Carbon::parse($rate->created_at)->locale('ar')->diffForHumans(),
                ];
            });

            // Process available_times for each activity
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

            // Update image paths with the full URL
            $activity->activityImages->each(function ($image) {
                $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;  // Fixed the path to /storage/
            });

            // Update tool image paths with the full URL
            // Attach tools with nested attributes and images
            $tools = $activityTools->where('activity_id', $activity->id);
            $formattedTools = [];

            foreach ($tools as $tool) {
                // $toolAttributes = $toolsAttributes->where('tool_id', $tool->id);
                $formattedAttributes = [];

                // foreach ($toolAttributes as $attribute) {
                //     $values = $toolAttributeValues->where('tool_attribute_id', $attribute->id)->map(function ($value) {
                //         return [
                //             'id' => $value->id,
                //             'tool_attribute_id' => $value->tool_attribute_id,
                //             'value' => $value->value,
                //             'price' => $value->price,
                //         ];
                //     });

                //     $formattedAttributes[] = [
                //         'id' => $attribute->id,
                //         'tool_id' => $attribute->tool_id,
                //         'attribute_name_en' => $attribute->attribute_name_en,
                //         'attribute_name_ar' => $attribute->attribute_name_ar,
                //         'options_values' => $values->values(),  // This will re-index the array starting from 0
                //     ];
                // }

                $formattedTools[] = [
                    'id' => $tool->id,
                    'name_en' => $tool->name_en,
                    'name_ar' => $tool->name_ar,
                    'description_en' => $tool->description_en,
                    'description_ar' => $tool->description_ar,
                    // 'tool_attributes' => $formattedAttributes,
                    'tool_images' => $tool->toolImages->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
                        ];
                    }),
                ];
            }

            $activity->tools = $formattedTools;
        });

        return $activities;
    }

    /* ActivityTypes */
    public function activityTypes($perPage)
    {
        return ActivityType::paginate($perPage);
    }

    public function findActivityType($id)
    {
        // Fetch the activity with related data
        $activityType = ActivityType::find($id);

        // Check if the activity exists
        if (!$activityType) {
            return response()->json([
                'message' => 'Activity type not found',
                'message_ar' => 'لم يتم العثور على نوع الرحلة',
            ], 404);
        }

        // Attach images with full paths
        // if ($activityType->relationLoaded('OfferImage')) {
        //     $activityType->offeractivityImage->transform(function ($image) {
        //         $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path; // Corrected path
        //         return $image;
        //     });
        // }

        return $activityType;
    }

    public function createActivityType(array $data)
    {
        return DB::transaction(function () use ($data) {
            $activityTypeData = [
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'image' => $data['image'] ?? null,  // Cleaner way to handle optional image
            ];

            return ActivityType::create($activityTypeData);
        });
    }

    public function updateActivityType($activityType, array $data)
    {
        $activityType->update($data);
        return $activityType;
    }

    public function deleteActivityType($activityType)
    {
        return $activityType->delete();
    }

    /* ActivityTypes */
}
