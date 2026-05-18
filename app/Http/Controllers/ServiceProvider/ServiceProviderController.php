<?php

namespace App\Http\Controllers\ServiceProvider;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityImage;
use App\Models\ActivityPlan;
use App\Models\ActivityTool;
use App\Models\Booking;
use App\Models\CommercialTool;
use App\Models\Rating;
use App\Models\Room;
use App\Models\Tool;
// use App\Models\ToolAttribute;
// use App\Models\ToolAttributeValues;
use App\Models\ToolImage;
use App\Models\User;
use App\Services\File\FileService;
use App\Services\OTPService;
use App\Services\TwilioService;
// <<<<<<< HEAD
use App\Services\WalletService;
// =======
// >>>>>>> 9606bd36a2ff7e0b94d75e575d5a842b01dd4274
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiceProviderController extends Controller
{
    public function __construct(
        public TwilioService $twilio,
        public OTPService $service,
        public FileService $fileService
    ) {}

    public function profile()
    {
        // Fetch the authenticated user
        $auth_user = Auth::user();
        $user_id = $auth_user->id;
        $user = User::with('userType')->find($user_id);

        // Eager load relationships to avoid N+1 queries
        $activities = Activity::with([
            'activityImages',
            'rate',  // Ensure this relationship exists
            'activityPlans',
            'tools.toolImages',  // Ensure this relationship exists
            // 'tools.toolAttributes.toolAttributeValues' // Ensure this relationship exists
        ])
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
                'duration',
                'start_date',
                'price',
                'capacity',
                'status_id',
                'lat',
                'long',
                'is_tourguideable',
                'is_photographer_available',
                'activity_days',
                'activity_times_start',
                'activity_times_end'
            )
            ->where('user_id', $user_id)
            ->orderBy('activities.id', 'desc')
            ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
            ->get();

        // Process each activity
        $activities->each(function ($activity) {
            // Calculate average rating (ensure 'rate' relationship exists)
            if ($activity->rate) {
                $ratings = $activity->rate->pluck('rating');
                $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 1, '.', ',') : 0;

                // Add individual ratings with timeAgo
                $activity->rate = $activity->rate->map(function ($rate) {
                    return [
                        'rating' => $rate->rating,
                        'timeAgo' => Carbon::parse($rate->created_at)->diffForHumans(),
                    ];
                });
            } else {
                $activity->average_rating = 0;
                $activity->rate = [];
            }

            // Process available_times for each activity
            $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
            $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

            $availableTimes = [];
            foreach ($activityDays as $index => $day) {
                if (isset($activityTimes[$index])) {
                    $date = Carbon::parse($day);  // Parse the date to get the day name
                    $dayName = $date->format('l');  // Get the full day name (e.g., "Monday")
                    $availableTimes[] = [
                        'day_name' => $dayName,  // Include only the day name
                        'time' => $activityTimes[$index],  // Include the time
                    ];
                }
            }
            $activity->available_times = $availableTimes;

            // Update image paths with the full URL
            if ($activity->activityImages) {
                $activity->activityImages->each(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/' . $image->image_path;
                });
            }
            // dd(env('APP_URL') . 'storage/');
            // Format tools with nested attributes and images
            if ($activity->tools) {
                $activity->tools = $activity->tools->map(function ($tool) {
                    // $toolAttributes = $tool->toolAttributes ?? collect();  // Ensure toolAttributes is not null
                    $toolImages = $tool->toolImages ?? collect();  // Ensure toolImages is not null

                    return [
                        'id' => $tool->id,
                        'name_en' => $tool->name_en,
                        'name_ar' => $tool->name_ar,
                        // 'tool_attributes' => $toolAttributes->map(function ($attribute) {
                        //     $values = $attribute->toolAttributeValues ?? collect();  // Ensure toolAttributeValues is not null
                        //     return [
                        //         'id' => $attribute->id,
                        //         'tool_id' => $attribute->tool_id,
                        //         'attribute_name_en' => $attribute->attribute_name_en,
                        //         'attribute_name_ar' => $attribute->attribute_name_ar,
                        //         'values' => $values->map(function ($value) {
                        //             return [
                        //                 'id' => $value->id,
                        //                 'tool_attribute_id' => $value->tool_attribute_id,
                        //                 'value' => $value->value,
                        //                 'price' => $value->price,
                        //             ];
                        //         }),
                        //     ];
                        // }),
                        'tool_images' => $toolImages->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'image_path' => env('APP_URL') . 'storage/' . $image->image_path,
                            ];
                        }),
                    ];
                });
            } else {
                $activity->tools = [];
            }
        });

        // Count the number of activities
        $activity_numbers = $activities->count();

        // Retrieve the user's profile image if available - safely handle missing file records
        $profileImage = null;
        if ($user->live_photo) {
            $file = DB::table('files')->find($user->live_photo);
            if ($file && $file->name) {
                $profileImage = env('APP_URL') . 'storage/' . $file->name;
            }
        }

        // Company Logo - safely handle missing file records
        $company_logo = null;
        if ($user->company_logo) {
            $file = DB::table('files')->find($user->company_logo);
            if ($file && $file->name) {
                $company_logo = env('APP_URL') . 'storage/' . $file->name;
                $profileImage = $company_logo;  // Override profile image with company logo if exists
            }
        }

        // National Id Image - safely handle missing file records
        $national_id_image = null;
        if ($user->national_id_image) {
            $file = DB::table('files')->find($user->national_id_image);
            if ($file && $file->name) {
                $national_id_image = env('APP_URL') . 'storage/' . $file->name;
            }
        }

        // TRN Image - safely handle missing file records
        $trn = null;
        if ($user->trn_image) {
            $file = DB::table('files')->find($user->trn_image);
            if ($file && $file->name) {
                $trn = env('APP_URL') . 'storage/' . $file->name;
            }
        }

        // Return user profile as JSON response
        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id' => $user_id,
                'user_details' => $user,
                'total_activity_numbers' => $activity_numbers,
                'activities' => $activities,
                'profile_image' => $profileImage,
                'national_id_image' => $national_id_image,
                'trn_image' => $trn,
                // 'company_logo' => $company_logo,
                'room_id' => Room::where('user_id', $user->id)->where('user_type', $user->acting_as)->first()->room_id ?? null,
            ],
        ]);
    }

    public function generateOTPforUpdateProfile()
    {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);
        $user = auth()->user();
        $phome_number = User::find($user->id)->phome_number;

        // Call the custom validation method
        // if (!$this->validateSaudiPhoneNumber($phome_number)) {
        //     return response()->json([
        //         'message' => 'The phone number is not valid.',
        //         'message_ar' => 'رقم الهاتف غير صالح.',
        //     ], 422);
        // }
        // Delete old OTPs for this user
        OTP::where('user_id', $user->id)->delete();

        // Save the OTP in the database
        OTP::create([
            'user_id' => $user->id,
            'phone_number' => $phome_number,
            'otp' => $otp,
            'created_at' => now(),
        ]);

        $this->twilio->sendMessage(
            $request->phone_number,
            $otp
        );
        return response()->json([
            'message' => 'OTP Sent Successfully',
            'message_ar' => 'تم إرسال رمز التحقق بنجاح',
            'phone_number' => $phome_number,
            'otp' => $otp,
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user_id = Auth::user()->id;
        $request->validate([
            'name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'country_code' => 'nullable',
            'gender' => 'nullable',
            'phone_number' => 'nullable|numeric|unique:users,phone_number,' . $user_id,
            'otp' => 'required_with:phone_number|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
                'message_ar' => 'لم يتم العثور على المستخدم.',
            ], 404);
        }
        // If phone number is being updated, OTP is required
        if ($request->filled('phone_number')) {
            $otpRecord = OTP::where('user_id', $user->id)
                // ->where('phone_number', $request->phone_number)
                ->where('created_at', '>', now()->subMinutes(5))
                ->latest()
                ->first();
            // dd($otpRecord);

            if (!$otpRecord || $otpRecord->otp !== $request->otp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP.',
                    'message_ar' => 'رمز التحقق غير صحيح.',
                ], 400);
            }
        }

        // Store old photo ID for cleanup
        $oldLivePhotoId = $user->live_photo;
        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // First update the user's live_photo to null to remove the foreign key constraint
            $user->live_photo = null;
            $user->save();

            // Now we can safely delete the old file
            if ($oldLivePhotoId) {
                $oldFile = DB::table('files')->find($oldLivePhotoId);
                if ($oldFile) {
                    // try {
                    // Delete the physical file
                    Storage::delete($oldFile->name);
                    // Delete the database record
                    DB::table('files')->where('id', $oldLivePhotoId)->delete();
                    // } catch (\Exception $e) {
                    //     // Log the error but don't fail the request
                    //     \Log::error('Failed to delete old profile image: ' . $e->getMessage());
                    // }
                }
            }

            // Upload and set the new photo - make sure we get just the ID
            $uploadResult = $this->fileService->upload($request->file('profile_image'), $user->id);

            // Handle both cases where upload might return object or just ID
            if (is_object($uploadResult)) {
                $livePhoto = $uploadResult->id;
            } elseif (is_array($uploadResult)) {
                $livePhoto = $uploadResult['id'];
            } else {
                $livePhoto = $uploadResult;
            }

            $user->live_photo = $livePhoto;
        }

        // Update basic details
        $user->update([
            'name' => $request->name ?? $user->name,
            'last_name' => $request->last_name ?? $user->last_name,
            'phone_number' => $request->filled('phone_number') ? $request->phone_number : $user->phone_number,
            'country_code' => $request->country_code ?? $user->country_code,
            'gender' => $request->gender ?? $user->gender,
        ]);

        // If phone number updated, also update bookings table
        if ($request->filled('phone_number')) {
            DB::table('bookings')
                ->where('user_id', $user->id)
                ->update(['phone_number' => $request->phone_number]);
        }

        // Count travel trips (bookings)
        $activity_numbers = DB::table('bookings')->where('user_id', $user->id)->count();

        // Profile image
        $profileImage = null;
        if ($user->live_photo) {
            $file = DB::table('files')->find($user->live_photo);
            if ($file) {
                $profileImage = env('APP_URL') . 'storage/' . $file->name;
            }
        }

        $activity_numbers = DB::table('bookings')->where('user_id', $user->id)->count();
        // Profile image
        if ($user->live_photo) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name = DB::table('files')->find($user->live_photo)->name;
            // Retrieve the user's profile image if available
            $profileImage = null;
            // Assuming the image is stored in 'storage'
            $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $profileImage = null;
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => $user,
                'profile_image' => $profileImage,
            ],
        ]);
    }

    public function tour_guide(Request $request)
    {
        $users = User::with('userType')
            ->where('tour_guide', 1)
            // ->get();
            ->paginate($request->per_page);

        foreach ($users as $user) {
            // Retrieve the user's profile image
            $profileImage = null;
            if ($user->live_photo) {
                $file = DB::table('files')->find($user->live_photo);
                $image_name = $file ? $file->name : null;

                if ($image_name) {
                    $profileImage = env('APP_URL') . 'storage/' . $image_name;
                }
            }
            $user->profile_image = $profileImage;

            // Fetch activities for the user
            $activities = Activity::with([
                'activityImages',
                'rate',
                'activityPlans',
                'tools.toolImages',
            ])
                ->select(
                    'activities.id',
                    'user_id',
                    'activity_type_id',
                    'title_en',
                    'title_ar',
                    'description_en',
                    'description_ar',
                    'city_name_en',
                    'city_name_ar',
                    'duration',
                    'start_date',
                    'price',
                    'capacity',
                    'lat',
                    'long',
                    'is_tourguideable',
                    'is_photographer_available',
                    'activity_days',
                    'activity_times'
                )
                ->where('user_id', $user->id)
                ->join('activity_statuses', 'activity_statuses.id', '=', 'activities.status_id')
                ->orderBy('id', 'desc')
                ->get();

            // Pre-fetch data for tools and attributes
            $activityIds = $activities->pluck('id')->toArray();
            $activityTools = Tool::whereIn('activity_id', $activityIds)->with('toolImages')->get();
            // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('id')->toArray())->get();
            // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id')->toArray())->get();

            // Process activities
            $activities->each(function ($activity) {
                if ($activity->rate) {
                    $ratings = $activity->rate->pluck('rating');
                    $activity->average_rating = $ratings->isNotEmpty() ? number_format($ratings->avg(), 1, '.', ',') : 0;
                    $activity->rate = $activity->rate->map(function ($rate) {
                        return [
                            'rating' => $rate->rating,
                            'timeAgo' => Carbon::parse($rate->created_at)->diffForHumans(),
                        ];
                    });
                } else {
                    $activity->average_rating = 0;
                    $activity->rate = [];
                }

                $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
                $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];
                $availableTimes = [];
                foreach ($activityDays as $index => $day) {
                    if (isset($activityTimes[$index])) {
                        $date = Carbon::parse($day);
                        $availableTimes[] = [
                            'day_name' => $date->format('l'),
                            'time' => $activityTimes[$index],
                        ];
                    }
                }
                $activity->available_times = $availableTimes;

                if ($activity->activityImages) {
                    $activity->activityImages->each(function ($image) {
                        $image->image_path = asset('storage/' . $image->image_path);
                    });
                }

                if ($activity->tools) {
                    $activity->tools = $activity->tools->map(function ($tool) {
                        // $tool->tool_attributes = $toolsAttributes->where('tool_id', $tool->id)->map(function ($att) use ($toolAttributeValues) {
                        //     $att->values = $toolAttributeValues->where('tool_attribute_id', $att->id)->values();  // Ensure values is an indexed array
                        //     return $att;
                        // })->values();  // Ensure tool_attributes is an indexed array
                        return $tool;
                    });
                }
            });

            // Attach activities and count to the user
            $user->activities = $activities;
            $user->total_activity_numbers = $activities->count();
        }

        // Return the response
        return response()->json([
            'status' => 'success',
            'data' => [
                'Tour_Guide_details' => $users,
            ],
        ]);
    }

    public function dashboard()
    {
        // Fetch the authenticated user
        $auth_user = Auth::user();
        $user_id = $auth_user->id;
        $user = User::with('userType')->find($user_id);

        // $activities = Activity::with(['activityImages', 'activityPlans'])
        // ->where('user_id', $user_id)
        // ->get();

        // $activities->each(function ($activity) {
        //     $activity->activityImages->each(function ($image) {
        //         $image->image_path = env('APP_URL') . 'storage/' . $image->image_path;
        //     });
        // });

        // Get all activity IDs for this user
        $numberOfTrips = Activity::where('user_id', $user_id)->count();

        // Step 1: Get all activity IDs for this user
        $activityIds = Activity::where('user_id', $user_id)->pluck('id');

        // Step 2: Count bookings with status_id = 3 for those activities
        $totalRequests = Booking::whereIn('activity_id', $activityIds)
            // ->where('status_id', 3)
            ->where('status_id', 1)
            ->count();

        $totalRatings = 0;
        $totalRatesCount = Rating::whereIn('activity_id', $activityIds)->count();
        $totalRatings += Rating::whereIn('activity_id', $activityIds)->sum('rating');

        $todayDayName = Carbon::now()->format('l');  // e.g., 'Monday'
        $todayDate = Carbon::now()->toDateString();  // e.g., '2025-06-16'

        // $totalRatings = 0;
        // $totalRatesCount = 0;
        // $totalRequests  = 0;

        // $activities = Activity::select('activities.*')
        //     ->join('bookings', 'activities.id', '=', 'bookings.activity_id')
        //     ->where('bookings.status_id', 3)
        //     ->where('activities.user_id', $user_id)
        //     ->with([
        //         'activityImages',
        //         'activityPlans',
        //         'bookings.booking_status',
        //         'bookings.customer'
        //     ])
        //     ->withCount([
        //         'bookings as guests' => function ($query) {
        //             $query->where('bookings.status_id', 3);
        //         }
        //     ])
        //     ->get();
        // ->unique('bookings.id') //  Remove duplicate activities
        // ->values();    //  Re-index collection

        // Optional: Load activities with their accepted bookings
        // $activities = Activity::with([
        //     'activityImages',
        //     'activityPlans',
        //     'bookings' => function ($q) {
        //         $q->where('status_id', 3); // only accepted
        //     },
        //     'bookings.booking_status',
        //     'bookings.customer'
        // ])
        // ->withCount([
        //         'bookings as guests' => function ($query) {
        //             $query->where('bookings.status_id', 3);
        //         }
        //     ])
        //     ->where('user_id', $user_id)
        //     ->whereHas('bookings') // This filters out activities with zero bookings
        //     ->get();

        $activities = Activity::with([
            'activityImages',
            'activityPlans',
            'activityType',
            'bookings' => function ($q) use ($todayDate, $todayDayName) {
                $q
                    // ->where('status_id', 5)  // only accepted
                    ->where(function ($query) use ($todayDate, $todayDayName) {
                        $query
                            ->whereDate('date', $todayDate)
                            ->orWhereRaw('date = ?', [$todayDayName]);
                    });
            },
            'bookings.booking_status',
            'bookings.customer'
        ])
            ->withCount([
                'bookings as guests' => function ($query) use ($todayDate, $todayDayName) {
                    $query
                        // ->where('status_id', 5)
                        ->where(function ($q) use ($todayDate, $todayDayName) {
                            $q
                                ->whereDate('date', $todayDate)
                                ->orWhereRaw('date = ?', [$todayDayName]);
                        });
                }
            ])
            ->where('user_id', $user_id)
            ->whereHas('bookings', function ($q) use ($todayDate, $todayDayName) {
                $q
                    ->where('status_id', 5)
                    ->where(function ($query) use ($todayDate, $todayDayName) {
                        $query
                            ->whereDate('date', $todayDate)
                            ->orWhereRaw('date = ?', [$todayDayName]);
                    });
            })
            ->get();

        // dd('sarah');

        // dd($activities);
        foreach ($activities as $trip) {
            if ($trip && $trip->activityImages) {
                $trip->activityImages->each(function ($image) {
                    $image->image_path = env('APP_URL') . 'storage/' . $image->image_path;
                    // $image->image_path = Str::startsWith($image->image_path, ['http://', 'https://'])
                    //     ? $image->image_path
                    //     : asset('storage/' . $image->image_path);
                });
            }

            // Loop through each booking in the activity
            foreach ($trip->bookings as $booking) {
                // $booking->customers->live_photo;

                // Retrieve the customer's profile image if available
                $customer = $booking->customer;  // Ensure this is the correct relation name

                $profileImage = null;

                if ($customer && $customer->live_photo) {
                    $file = DB::table('files')->find($customer->live_photo);

                    if ($file && isset($file->name)) {
                        $profileImage = env('APP_URL') . 'storage/' . $file->name;
                    }
                }

                $customer->profileImage = $profileImage;
                $booking->customer->profile_image = $profileImage;

                $toolIds = !empty($booking->tool_id) ? explode(',', $booking->tool_id) : [];
                $toolCapacities = !empty($booking->tool_capacity) ? explode(',', $booking->tool_capacity) : [];

                // Fetch tools for this booking
                $tools = CommercialTool::with('toolImages')
                    ->whereIn('id', $toolIds)
                    ->get();

                $selected_tools = $tools->map(function ($tool) use ($toolIds, $toolCapacities) {
                    $index = array_search($tool->id, $toolIds);
                    $tool->capacity = $toolCapacities[$index] ?? 1;

                    $tool->tool_images = $tool->toolImages->map(function ($image) {
                        $image->image_path = env('APP_URL') . 'storage/' . $image->image_path;
                        return $image;
                    })->values();

                    return $tool;
                });

                // Attach tools to the booking
                $booking->selected_tools = $selected_tools;
            }

            // $numberOfRequests =  $trip->bookings->where('status_id', 3)->count();
            // $totalRequests += $numberOfRequests;
            // echo 'Number of Guests ' . $trip->guests . ' , ';
            // echo 'Number of Requests ' .$numberOfRequests.' , ';
            // echo 'Total of Requests ' .$totalRequests.' , ';
            // $totalRatesCount = Rating::where('activity_id', $trip->id)->count();
            // $totalRatings += Rating::where('activity_id', $trip->id)->sum('rating');
        }
        $tripRatesAvg = $totalRatesCount > 0 ? $totalRatings / $totalRatesCount : 0;
        // echo 'Total of Requests out the loop ' .$totalRequests.' , ';

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_details' => $user,
                'numberOfActivities' => $numberOfTrips,
                'numberOfRequests' => $totalRequests,
                'activityRatesAvg' => $tripRatesAvg,
                'activities' => $activities,
                'revenueToday' => $activities->sum(function ($activity) {
                    return $activity->bookings->sum(function ($booking) {
                        $toolsTotal = collect($booking->selected_tools ?? [])->sum(function ($tool) {
                            return $tool->price * $tool->capacity;
                        });

                        return $booking->total_price + $toolsTotal;
                    });
                }),
            ],
        ]);
    }
}
