<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\PushNotificationRequest;
use App\Mail\BookingNotificationMail;
use App\Models\Activity;
use App\Models\ActivityAttendence;
use App\Models\ActivityCapacity;
use App\Models\ActivityPlan;
use App\Models\ActivityTool;
use App\Models\Booking;
use App\Models\CommercialTool;
use App\Models\Tool;
// use App\Models\ToolAttribute;
// use App\Models\ToolAttributeValues;
use App\Models\User;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPMailer\PHPMailer\PHPMailer;

require base_path('vendor/phpmailer/phpmailer/src/PHPMailer.php');
require base_path('vendor/phpmailer/phpmailer/src/SMTP.php');
require base_path('vendor/phpmailer/phpmailer/src/Exception.php');

class BookingController extends Controller
{
    // Validate Saudi mobile number
    public function validateSaudiPhoneNumber($phone)
    {
        // Normalize the phone number by removing spaces or unwanted characters
        $phone = trim($phone);

        // Check if the phone number starts with '+9665' and is 12 characters long
        if (substr($phone, 0, 5) === '+9665' && strlen($phone) === 13) {
            // Ensure all remaining characters are digits
            $remainingDigits = substr($phone, 5);  // Get everything after '+9665'
            if (ctype_digit($remainingDigits)) {
                return true;  // Valid phone number
            }
        }

        return false;  // Invalid phone number
    }

    // public function index(Request $request)
    // {
    //     $perPage = $request->input('per_page', 10);  // Default to 10 items per page if not provided

    //     $booking_list = Booking::with(['activity.activityImages', 'booking_status', 'customer'])
    //         ->where('user_id', auth()->id())
    //         ->paginate($perPage);

    //     // Get all activities related to the bookings
    //     $activityIds = $booking_list->pluck('activity.id')->filter()->unique()->toArray();

    //     // Fetch related data
    //     $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //     $activityTools = Tool::whereIn('activity_id', $activityIds)->with('toolImages')->get();
    //     $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('tool_id'))->get();
    //     $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id'))->get();

    //     // Process available_times, activity plans, tools, and attach related data
    //     $booking_list->getCollection()->transform(function ($booking) use ($activityPlans, $activityTools, $toolsAttributes, $toolAttributeValues) {
    //         $activity = $booking->activity;

    //         if ($activity) {
    //             // Process available_times
    //             $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
    //             $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

    //             $availableTimes = [];
    //             foreach ($activityDays as $index => $day) {
    //                 $date = Carbon::parse($day);
    //                 $dayName = $date->format('l');
    //                 $availableTimes[] = [
    //                     'date' => $day,
    //                     'day_name' => $dayName,
    //                     'time' => $activityTimes[$index] ?? null,
    //                 ];
    //             }
    //             $activity->available_times = $availableTimes;

    //             // Attach activity plans
    //             $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

    //             // Attach tools with nested attributes and images
    //             $tools = $activityTools->where('activity_id', $activity->id);
    //             $formattedTools = [];

    //             foreach ($tools as $tool) {
    //                 $toolAttributes = $toolsAttributes->where('tool_id', $tool->id);
    //                 $formattedAttributes = [];

    //                 foreach ($toolAttributes as $attribute) {
    //                     $values = $toolAttributeValues->where('tool_attribute_id', $attribute->id)->map(function ($value) {
    //                         return [
    //                             'book_id' => $value->id,
    //                             'tool_attribute_id' => $value->tool_attribute_id,
    //                             'value' => $value->value,
    //                             'price' => $value->price,
    //                         ];
    //                     });

    //                     $formattedAttributes[] = [
    //                         'book_id' => $attribute->id,
    //                         'tool_id' => $attribute->tool_id,
    //                         'attribute_name_en' => $attribute->attribute_name_en,
    //                         'attribute_name_ar' => $attribute->attribute_name_ar,
    //                         'options_values' => $values,
    //                     ];
    //                 }

    //                 $formattedTools[] = [
    //                     'book_id' => $tool->id,
    //                     'name_en' => $tool->name_en,
    //                     'name_ar' => $tool->name_ar,
    //                     'description_en' => $tool->description_en,
    //                     'description_ar' => $tool->description_ar,
    //                     'tool_attributes' => $formattedAttributes,
    //                     'tool_images' => $tool->toolImages->map(function ($image) {
    //                         return [
    //                             'book_id' => $image->id,
    //                             'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
    //                         ];
    //                     }),
    //                 ];
    //             }

    //             $activity->tools = $formattedTools;

    //             // Update image paths
    //             $activity->activityImages->each(function ($image) {
    //                 $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
    //             });
    //         }

    //         return $booking;
    //     });

    //     return response()->json($booking_list);
    // }
    // COOOOOOOOOOOLL
    // public function index(Request $request)
    // {
    //     $perPage = $request->input('per_page', 10);  // Default to 10 items per page if not provided
    //     $status_id = $request->input('status_id');  // Default to 10 items per page if not provided

    //     $booking_list = Booking::with(['activity.activityImages','activity.activityType', 'booking_status', 'customer'])
    //         ->where('user_id', auth()->id());

    //     if ($status_id == 0) {  // previous
    //         $booking_list = $booking_list->where('date', '<', date('Y-m-d'));
    //     } else {  // current
    //         $booking_list = $booking_list->where('date', '>=', date('Y-m-d'));
    //     }

    //     $booking_list = $booking_list->paginate($perPage);

    //     // Get all activities related to the bookings
    //     $activityIds = $booking_list->pluck('activity.id')->filter()->unique()->toArray();

    //     // Fetch related data
    //     $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
    //     $activityTools = Tool::whereIn('activity_id', $activityIds)->with('toolImages')->get();
    //     $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('tool_id'))->get();
    //     $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id'))->get();

    //     // Process available_times, activity plans, tools, and attach related data
    //     $booking_list->getCollection()->transform(function ($booking) use ($activityPlans, $activityTools, $toolsAttributes, $toolAttributeValues) {
    //         $activity = $booking->activity;

    //         if ($activity) {
    //             // Process available_times
    //             $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
    //             $activityTimes = $activity->activity_times ? explode(',', $activity->activity_times) : [];

    //             $availableTimes = [];
    //             foreach ($activityDays as $index => $day) {
    //                 $date = Carbon::parse($day);
    //                 $dayName = $date->format('l');
    //                 $availableTimes[] = [
    //                     'date' => $day,
    //                     'day_name' => $dayName,
    //                     'time' => $activityTimes[$index] ?? null,
    //                 ];
    //             }
    //             $activity->available_times = $availableTimes;

    //             // Attach activity plans
    //             $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

    //             // Attach tools with nested attributes and images
    //             $tools = $activityTools->where('activity_id', $activity->id);
    //             $formattedTools = [];

    //             foreach ($tools as $tool) {
    //                 $toolAttributes = $toolsAttributes->where('tool_id', $tool->id);
    //                 $formattedAttributes = [];

    //                 foreach ($toolAttributes as $attribute) {
    //                     $values = $toolAttributeValues->where('tool_attribute_id', $attribute->id)->map(function ($value) {
    //                         return [
    //                             'book_id' => $value->id,
    //                             'tool_attribute_id' => $value->tool_attribute_id,
    //                             'value' => $value->value,
    //                             'price' => $value->price,
    //                         ];
    //                     });

    //                     $formattedAttributes[] = [
    //                         'book_id' => $attribute->id,
    //                         'tool_id' => $attribute->tool_id,
    //                         'attribute_name_en' => $attribute->attribute_name_en,
    //                         'attribute_name_ar' => $attribute->attribute_name_ar,
    //                         'options_values' => $values,
    //                     ];
    //                 }

    //                 $formattedTools[] = [
    //                     'book_id' => $tool->id,
    //                     'name_en' => $tool->name_en,
    //                     'name_ar' => $tool->name_ar,
    //                     'description_en' => $tool->description_en,
    //                     'description_ar' => $tool->description_ar,
    //                     'tool_attributes' => $formattedAttributes,
    //                     'tool_images' => $tool->toolImages->map(function ($image) {
    //                         return [
    //                             'book_id' => $image->id,
    //                             'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
    //                         ];
    //                     }),
    //                 ];
    //             }

    //             $activity->tools = $formattedTools;

    //             // Update image paths
    //             $activity->activityImages->each(function ($image) {
    //                 $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
    //             });
    //         }

    //         return $booking;
    //     });

    //     return response()->json($booking_list);
    // }
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $status_id = $request->input('status_id');

        $booking_list = Booking::with([
            // 'activity.activityImages',
            'activity.activityType',
            'activity.serviceprovider',
            'booking_status',
            'customer',
            'customer.userImage'
        ])
            // ->where('id', 458) // for test
            ->where('user_id', auth()->id())
            ->orderBy('date', 'asc');

        // Filter by status
        if ($status_id == 0) {
            $booking_list->where('date', '<', date('Y-m-d'));  // Previous
        } else {
            $booking_list->where('date', '>=', date('Y-m-d'));  // Current & upcoming
        }

        $booking_list = $booking_list->paginate($perPage);

        // Fetch related activity data
        $activityIds = $booking_list->pluck('activity.id')->filter()->unique()->toArray();

        $activityPlans = ActivityPlan::whereIn('activity_id', $activityIds)->get();
        $activityTools = Tool::whereIn('activity_id', $activityIds)->with('toolImages')->get();
        // $toolsAttributes = ToolAttribute::whereIn('tool_id', $activityTools->pluck('tool_id'))->get();
        // $toolAttributeValues = ToolAttributeValues::whereIn('tool_attribute_id', $toolsAttributes->pluck('id'))->get();

        $booking_list->getCollection()->transform(function ($booking) use ($activityPlans) {
            // dd($booking);

            // $booking->time = $booking->time;

            $activity = $booking->activity;
            // dd($activity);

            if ($activity) {
                // Prepare available_times
                $activityDays = $activity->activity_days ? explode(',', $activity->activity_days) : [];
                $activityTimesStart = $activity->activity_times_start ? explode(',', $activity->activity_times_start) : [];
                $activityTimesEnd = $activity->activity_times_end ? explode(',', $activity->activity_times_end) : [];
                $activitySingleDates = $activity->activity_single_dates ? explode(',', $activity->activity_single_dates) : [];

                // $availableTimes = [];

                // foreach ($activitySingleDates as $dateString) {
                //     try {
                //         $date = Carbon::parse($dateString);
                //         $dayName = strtolower($date->format('l'));

                //         foreach ($activityDays as $index => $day) {
                //             if (strtolower(trim($day)) === $dayName) {
                //                 $availableTimes[] = [
                //                     'date' => $date->toDateString(),
                //                     'day_name' => $date->format('l'),
                //                     'start_time' => $activityTimesStart[$index] ?? null,
                //                     'end_time' => $activityTimesEnd[$index] ?? null,
                //                 ];
                //                 break;
                //             }
                //         }
                //     } catch (\Exception $e) {
                //         continue;
                //     }
                // }

                // $activity->available_times = $availableTimes;

                // Attach activity plans
                $activity->activity_plans = $activityPlans->where('activity_id', $activity->id)->values();

                // Update image paths
                // $activity->activityImages->each(function ($image) {
                //     $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
                // });
                $activity->activityImages->each(function ($image) {
                    if (!Str::startsWith($image->image_path, ['http://', 'https://'])) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . ltrim($image->image_path, '/');
                    }
                });

                if ($booking->customer && $booking->customer->relationLoaded('userImage')) {
                    $booking->customer->user_image_url = $booking->customer->user_image_url;  // accessor
                }

                // Handle tools sent in request
                $activityToolRelations = ActivityTool::where('activity_id', $activity->id)->get();

                // parse tool IDs and capacities from the booking
                $toolIds = !empty($booking->tool_id) ? explode(',', $booking->tool_id) : [];
                $toolCapacities = !empty($booking->tool_capacity) ? explode(',', $booking->tool_capacity) : [];

                // Fetch tools
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

                // Attach tools to booking
                $booking->selected_tools = $selected_tools;
            }

            return $booking;
        });
        // dd($booking_list);
        return response()->json($booking_list);
    }

    // Book Activity
    // public function store(Request $request) ///// OLD
    // {
    //     DB::beginTransaction(); // Start the transaction
    //     $start_date = Activity::find($request->activity_id)->start_date;
    //     // dd($start_date);
    //     try {
    //         // Validate the incoming request
    //         try {
    //             $request->validate([
    //                 'photographer' => 'nullable|numeric', // Optional photographer
    //                 'tour_guide' => 'nullable|numeric', // Optional tour_guide
    //                 'tool_id' => ['array'], // Not required, but if provided, it must be an array
    //                 'tool_id.*.id' => ['required_with:tool_id', 'exists:tools,id'],
    //                 'tool_id.*.tool_attribute_value' => ['required_with:tool_id', 'array'],
    //                 'tool_id.*.tool_attribute_value.*' => ['required_with:tool_id', 'integer'],
    //             ]);

    //             $request->validate([
    //                 'time' => $start_date == null
    //                     ? 'required|date_format:g:i A' // If '$start_date' is null, validate the time format
    //                     : 'nullable', // Otherwise, the 'time' field is optional
    //             ]);

    //             $request->validate([
    //                 'date' => $start_date != null
    //                     ? 'required|date_format:Y-m-d' // If '$start_date' is 1, ensure proper date format
    //                     : 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday', // Otherwise, ensure it's a valid day
    //             ]);
    //         } catch (ValidationException $e) {
    //             // Capture validation errors and return them in the response
    //             return response()->json([
    //                 'message' => 'Validation failed.',
    //                 'message_ar' => 'فشل التحقق من الصحة.',
    //                 'errors' => $e->errors(), // This gives an array of all validation errors
    //             ], 422); // Unprocessable Entity
    //         }

    //         // Determine user data (Authenticated or provided in request)
    //         $user_id = auth()->id();
    //         $user_name = auth()->user()->name;
    //         $phone_number = auth()->user()->phone_number;

    //         // Check if the phone number already exists for the activity
    //         $existingBooking = Booking::where('activity_id', $request->activity_id)
    //             ->where('user_id', $user_id)
    //             ->first();

    //         if ($existingBooking) {
    //             return response()->json([
    //                 'message' => 'This phone number is already associated with the selected activity.',
    //                 'message_ar' => 'رقم الهاتف هذا مرتبط بالفعل بالرحلة المحدد.',
    //             ], 400); // Return a bad request response
    //         }

    //         // Validate phone number (for Saudi Arabia)
    //         if (!$this->validateSaudiPhoneNumber($phone_number)) {
    //             return response()->json([
    //                 'message' => 'The phone number is not valid.',
    //                 'message_ar' => 'رقم الهاتف غير صالح.',
    //             ], 422);
    //         }

    //         // Generate a random number
    //         $randomNumber = random_int(100000, 999999); // Example: 6-digit random number

    //         $activity = Activity::find($request->activity_id);
    //         $sub_price = 0;
    //         if (!empty($request->tour_guide)) {
    //             $sub_price += $activity->tourguide_price;
    //         }
    //         if (!empty($request->photographer)) {
    //             $sub_price += $activity->photographer_price;
    //         }
    //         $total_price = $activity->price + $sub_price;
    //         // dd($total_price);

    //         if (!empty($request->tool_id)) {
    //             // Get all available tools for the activity
    //             $available_tools = Tool::with(['toolAttribute', 'toolAttribute.ToolAttributeValues'])->where('activity_id', $request->activity_id)->get();
    //             $available_tool_ids = $available_tools->pluck('id')->toArray();

    //             // Extract tool IDs from the request
    //             $selected_tools = array_column($request->tool_id, 'id'); // Extract only IDs

    //             // Validate tools
    //             $valid_tool_ids = array_intersect($selected_tools, $available_tool_ids);
    //             $invalid_tool_ids = array_diff($selected_tools, $available_tool_ids);

    //             if (!empty($invalid_tool_ids)) {
    //                 return response()->json([
    //                     'message' => 'Some tool IDs are not valid.',
    //                     'message_ar' => 'بعض معرفات الأدوات غير صحيحة',
    //                     'invalid_tools' => array_values($invalid_tool_ids),
    //                     'available_tools' => $available_tools,
    //                 ], 422);
    //             }

    //             // Extract all tool_attribute_values from the selected tools
    //             $selected_tool_attribute_values = [];
    //             foreach ($request->tool_id as $tool) {
    //                 // if (isset($tool['tool_attribute_value'])) {
    //                 //     $selected_tool_attribute_values = array_merge($selected_tool_attribute_values, $tool['tool_attribute_value']);
    //                 // }

    //                 if (!empty($tool['tool_attribute_value']) && is_array($tool['tool_attribute_value'])) {
    //                     $selected_tool_attribute_values = array_merge($selected_tool_attribute_values, $tool['tool_attribute_value']);
    //                 }
    //             }

    //             // Find the activity
    //             $activity = Activity::find($request->activity_id);
    //             if (!$activity) {
    //                 return response()->json([
    //                     'message' => 'Activity not found.',
    //                     'message_ar' => 'الرحلة غير موجود'
    //                 ], 404);
    //             }

    //             // $sub_price = 0;

    //             // if ($request->tour_guide == 1) {
    //             //     $sub_price += $activity->tourguide_price;
    //             // }
    //             // if ($request->photographer == 1) {
    //             //     $sub_price += $activity->photographer_price;
    //             // }

    //             // if (!empty($request->tour_guide)) {
    //             //     $sub_price += $activity->tourguide_price;
    //             // }
    //             // if (!empty($request->photographer)) {
    //             //     $sub_price += $activity->photographer_price;
    //             // }

    //             $tool_quantity = $request->capacity ?? 1;
    //             // dd($sub_price);

    //             // Calculate tool attributes' price
    //             $tool_att_val_sum_price = ToolAttribute::whereIn('tool_id', $valid_tool_ids)
    //                 ->join('tools', 'tools.id', '=', 'tool_attributes.tool_id')
    //                 ->join('tool_attribute_values', 'tool_attributes.id', '=', 'tool_attribute_values.tool_attribute_id')
    //                 ->whereIn('tool_attribute_values.id', $selected_tool_attribute_values) // Now correctly filtering
    //                 ->get();

    //             foreach ($tool_att_val_sum_price as $pppp) {
    //                 $sub_price += $pppp->price * $tool_quantity;
    //             }

    //             $total_price = $activity->price + $sub_price;
    //         }
    //         // dd($tool_att_val_sum_price);
    //         // dd($sub_price);

    //         // Prepare booking data
    //         $bookingData = [
    //             'activity_id' => $request->activity_id,
    //             // 'tool_id' => $tool_id = is_array($valid_tool_ids) ? implode(',', $valid_tool_ids) : $request->tool_id,
    //             // 'tool_id' => is_array($valid_tool_ids) ? implode(',', $valid_tool_ids) : ($request->tool_id ?: null),
    //             'tool_id' => $request->tool_id ? (is_array($valid_tool_ids) ? implode(',', $valid_tool_ids) : $request->tool_id) : NULL,

    //             'date' => $request->date,
    //             'time' => ($start_date == null) ? $request->time : null,
    //             'capacity' => $request->capacity,
    //             'tour_guide' => $request->tour_guide,
    //             'photographer' => $request->photographer,
    //             'phone_number' => $phone_number,
    //             'user_name' => $user_name,
    //             'user_id' => $user_id,
    //             'code' => $randomNumber,
    //             'total_price' => $total_price,
    //         ];
    //         // dd($bookingData);

    //         $activity_capacity = $activity->capacity;

    //         // Check if the requested capacity is available
    //         $bookedCapacity = $activity->bookings()->sum('capacity'); // Total booked capacity
    //         $availableCapacity = $activity_capacity - $bookedCapacity;

    //         if ($start_date == null) { // Single day activity
    //             if (!empty($activity->activity_days) && !empty($activity->activity_times)) {
    //                 // Check if the requested date and time are available
    //                 $availableTimes = $this->getAvailableTimes($activity);

    //                 $dateMatches = false;
    //                 $timeMatches = false;

    //                 foreach ($availableTimes as $availableTime) {
    //                     if ($availableTime['day_name'] === $request->date && $availableTime['time'] === $request->time) {
    //                         $dateMatches = true;
    //                         $timeMatches = true;
    //                         break;
    //                     }
    //                 }

    //                 if ($dateMatches && $timeMatches) {
    //                     if ($availableCapacity >= $request->capacity) {
    //                         // Create the booking record
    //                         $book = Booking::create($bookingData);
    //                         // Update the activity's remaining capacity
    //                         $activity->update(['capacity' => $activity->capacity - $request->capacity]);
    //                         // Commit the transaction
    //                         DB::commit();

    //                         // Send notification
    //                         $this->sendBookingNotification($activity, $user_id, 'reservation_booked');

    //                         return response()->json([
    //                             'message' => 'Booked Successfully.',
    //                             'message_ar' => 'تم الحجز بنجاح.',
    //                             'data' => $book,
    //                             // 'selected_tools' => $tool_att_val_sum_price ?? 0,
    //                             'selected_tools' => isset($selected_tools) ? Tool::whereIn('id', $selected_tools)->get() : null,
    //                             // 'selected_tools' => $selected_tools,
    //                         ], 201); // HTTP Status 201 for successful creation
    //                     } else {
    //                         // Handle no available seats
    //                         $this->sendBookingNotification($activity, $activity->user_id, 'no_avaliable_seats');
    //                         DB::commit();

    //                         return response()->json([
    //                             'error_type' => 'capacity',
    //                             'message' => 'No available seats.',
    //                             'message_ar' => 'لا يوجد مقاعد متاحة',
    //                             'options' => [
    //                                 'cancel' => 'You can cancel the booking attempt.',
    //                                 'cancel_ar' => 'يمكنك إلغاء محاولة الحجز.',
    //                                 'waiting_list' => 'You can add yourself to the waiting list.',
    //                                 'waiting_list_ar' => 'يمكنك إضافة نفسك إلى قائمة الانتظار.',
    //                             ],
    //                         ], 400); // HTTP Status 400 for bad request
    //                     }
    //                 } else {
    //                     // Return an error if the date or time doesn't match
    //                     return response()->json([
    //                         'error_type' => 'date and time',
    //                         'message' => 'The requested date and time are not available for this activity.',
    //                         'message_ar' => 'التاريخ والوقت المطلوبان غير متاحين لهذه الرحلة.',
    //                         'available_times' => $availableTimes,
    //                     ], 400);
    //                 }
    //             }
    //             // else {
    //             //     return response()->json([
    //             //         'message' => 'This is not a single activity, please turn the plan field to 1.',
    //             //         'message' => 'هذا ليس رحلةًا واحدًا، يرجى تحويل حقل الخطة إلى 1.',
    //             //     ], 404);
    //             // }
    //         } else { // Plan-based activity
    //             // dd('sarah');
    //             // Fetch available dates for the given activity_id
    //             $available_dates = ActivityPlan::where('activity_id', $request->activity_id)
    //                 ->orderBy('dates', 'asc')
    //                 ->pluck('dates');

    //             // Get today's date
    //             $today = Carbon::today();
    //             $currentDay = $today->day;
    //             $currentMonth = $today->month;

    //             // Generate dates for the next 2 years
    //             $generatedDates = $this->generateValidDates($available_dates, $today);

    //             // Find the next available date
    //             $nextAvailableDate = $this->getNextAvailableDate($generatedDates, $today);

    //             // if ($nextAvailableDate) { // with Nada
    //             // Check if the requested date matches the next available date
    //             // $requestDate = Carbon::parse($request->date);
    //             // if ($requestDate->toDateString() === $nextAvailableDate) {
    //             if ($availableCapacity >= $request->capacity) {
    //                 // Create the booking record
    //                 $book = Booking::create($bookingData);
    //                 // Update the activity's remaining capacity
    //                 $activity->update(['capacity' => $activity->capacity - $request->capacity]);
    //                 DB::commit();

    //                 // Send notification
    //                 $this->sendBookingNotification($activity, $user_id, 'reservation_booked');

    //                 return response()->json([
    //                     'message' => 'Booked Successfully.',
    //                     'message_ar' => 'تم الحجز بنجاح.',
    //                     'data' => [
    //                         'book_data' => $book,
    //                         // 'selected_tools' => $request->tool_id ? $tool_att_val_sum_price : null,
    //                         'selected_tools' => isset($selected_tools) ? Tool::whereIn('id', $selected_tools)->get() : null,
    //                     ]
    //                 ], 201);
    //             } else {
    //                 // Handle no available seats
    //                 $this->sendBookingNotification($activity, $activity->user_id, 'no_avaliable_seats');
    //                 DB::commit();

    //                 return response()->json([
    //                     'error_type' => 'capacity',
    //                     'message' => 'No available seats.',
    //                     'message_ar' => 'لا يوجد مقاعد متاحة',
    //                     'options' => [
    //                         'cancel' => 'You can cancel the booking attempt.',
    //                         'cancel_ar' => 'يمكنك إلغاء محاولة الحجز.',
    //                         'waiting_list' => 'You can add yourself to the waiting list.',
    //                         'waiting_list_ar' => 'يمكنك إضافة نفسك إلى قائمة الانتظار.',
    //                     ],
    //                 ], 400);
    //             }
    //             // } else {
    //             // Return the next available date
    //             // return response()->json([
    //             //     'error_type' => 'date',
    //             //     'message' => 'The requested date is not available. The next available date is: ' . $nextAvailableDate,
    //             //     'message_ar' => 'التاريخ المطلوب غير متوفر. التاريخ المتاح التالي هو: ' . $nextAvailableDate,
    //             //     'next_available_date' => $nextAvailableDate,
    //             // ], 400);
    //             // }
    //             // } else { // with Nada
    //             //     return response()->json([
    //             //         'message' => 'No available dates found for this activity.',
    //             //         'message_ar' => 'لم يتم العثور على تواريخ متاحة لهذه الرحلة',
    //             //     ], 404);
    //             // }
    //         }

    //         // Check if the activity is fully booked
    //         if ($bookedCapacity == $activity_capacity) {
    //             $this->sendBookingNotification($activity, $activity->user_id, 'activity_completed');
    //         }
    //     } catch (\Exception $e) {
    //         // Roll back the transaction in case of an error
    //         DB::rollBack();

    //         // Return error response
    //         return response()->json([
    //             'message' => 'An error occurred while processing your request.',
    //             'message_ar' => 'حدث خطأ أثناء معالجة طلبك.',
    //             'error' => $e->getMessage(),
    //         ], 500); // HTTP Status 500 for internal server error
    //     }
    // }
    public function store(Request $request)
    {
        try {
            $request->validate([
                'photographer' => 'nullable|numeric',  // Optional photographer
                'tour_guide' => 'nullable|numeric',  // Optional tour_guide
                'date' => 'required|date_format:Y-m-d|after:today',
                'tool' => ['array'],  // Not required, but if provided, it must be an array
                'tool.*.id' => ['required_with:tool', 'exists:commercial_tools,id'],
                'tool.*.capacity' => ['required_with:tool', 'integer'],
            ]);
        } catch (ValidationException $e) {
            // Capture validation errors and return them in the response
            return response()->json([
                'message' => 'Validation failed.',
                'message_ar' => 'فشل التحقق من الصحة.',
                'errors' => $e->errors(),  // This gives an array of all validation errors
            ], 422);  // Unprocessable Entity
        }

        // Determine user data (Authenticated or provided in request)
        $user_id = auth()->id();
        $user_name = auth()->user()->name;
        $phone_number = auth()->user()->phone_number;

        // Check if the phone number already exists for the activity
        // $existingBooking = Booking::where('activity_id', $request->activity_id)
        //     ->where('user_id', $user_id)
        //     ->first();

        // if ($existingBooking) {
        //     return response()->json([
        //         'message' => 'This phone number is already associated with the selected activity.',
        //         'message_ar' => 'رقم الهاتف هذا مرتبط بالفعل بالرحلة المحدد.',
        //     ], 400); // Return a bad request response
        // }

        // // Validate phone number (for Saudi Arabia)
        // if (!$this->validateSaudiPhoneNumber($phone_number)) {
        //     return response()->json([
        //         'message' => 'The phone number is not valid.',
        //         'message_ar' => 'رقم الهاتف غير صالح.',
        //     ], 422);
        // }
        if ($user_id) {
            // check the activity is avalible or not according to date and avalible capacity
            $activity_details = Activity::with(['activityImages'])->find($request->activity_id);

            $activityDays = $activity_details->activity_days ? explode(',', $activity_details->activity_days) : [];
            $activityTimesStart = $activity_details->activity_times_start ? explode(',', $activity_details->activity_times_start) : [];
            $activityTimesEnd = $activity_details->activity_times_end ? explode(',', $activity_details->activity_times_end) : [];
            $activitySingleDates = $activity_details->activity_single_dates ? explode(',', $activity_details->activity_single_dates) : [];
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

            $activity_details->available_times = $availableTimes;

            $activity_details->activity_images = $activity_details->activityImages->map(function ($image) {
                $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
                return $image;
            })->values();
            // dd($activity_details);

            // Generate a random number
            $randomNumber = random_int(100000, 999999);  // Example: 6-digit random number

            // $activity = Activity::find($request->activity_id);
            $sub_price = 0;
            if (!empty($request->tour_guide)) {
                $sub_price += $activity_details->tourguide_price;
            }
            if (!empty($request->photographer)) {
                $sub_price += $activity_details->photographer_price;
            }

            // dd($total_price);
            $selected_tool_ids = [];
            $tool_capacities = [];
            $totalCapacityTool = 0;
            // $sub_price = 0;

            if ($request->filled('tool')) {
                $tools = ActivityTool::where('activity_id', $request->activity_id)->get();
                $tool_ids = $tools->pluck('commercial_tool_id');
                $tools_details = CommercialTool::with(['toolImages'])->whereIn('id', $tool_ids)->get();
                $tools_details->transform(function ($tool) {
                    // Convert tool_images to an indexed array
                    $tool->tool_images = $tool->toolImages->map(function ($image) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        return $image;
                    })->values();  // Ensure tool_images is an indexed array

                    return $tool;
                });

                $selected_tool_ids = [];
                $tool_capacities = [];
                $totalCapacityTool = 0;
                foreach ($request->tool as $index => $toool) {
                    $tool_id = $toool['id'] ?? null;
                    $capacity = $toool['capacity'] ?? 1;
                    $totalCapacityTool += $toool['capacity'];
                    // echo("Capacity for tool[$index] is ".$toool['capacity'].' <br/>');
                    // echo('<br/> Total Capacity is '.$totalCapacityTool.'<br/>');

                    $selected_tool_ids[] = $tool_id;
                    $tool_capacities[] = $capacity;

                    // if ($totalCapacityTool > $request->capacity) {
                    //     return response()->json([
                    //         'message' => "Total tool capcity should not be more than the people capcity it should be at most $request->capacity not $totalCapacityTool",
                    //         'message_ar' => 'اجمال عدد الادوات يجب الا يزيد عن عدد الاشخاص',
                    //     ], 422);
                    // }

                    $sub_price += $tools_details[$index]['price'] * $toool['capacity'];
                }

                // dd($sub_price);
            }

            // activity_price
            $activity_price = $activity_details->price * $request->capacity;
            $total_price = $activity_price + $sub_price;

            // dd($sub_price);

            // Get the lowercase day name from the request date
            $dateToFind = strtolower(date('l', strtotime($request->date)));  // e.g., "monday"

            $matchedTime = null;

            // Convert DB values into arrays
            $activityDays = array_map('strtolower', explode(',', $activity_details->activity_days));  // ensure all are lowercase
            $activityTimesStart = $activity_details->activity_times_start
                ? explode(',', $activity_details->activity_times_start)
                : [];

            // Match by index
            $index = array_search($dateToFind, $activityDays);
            if ($index !== false && isset($activityTimesStart[$index])) {
                $matchedTime = $activityTimesStart[$index];
            }

            // Output
            // if ($matchedTime) {
            //     echo "Time for {$dateToFind} is: {$matchedTime}";
            // } else {
            //     echo "No time found for {$dateToFind}";
            // }

            // Prepare booking data
            $bookingData = [
                'activity_id' => $request->activity_id,
                // 'tool_id' => $tool_id = is_array($valid_tool_ids) ? implode(',', $valid_tool_ids) : $request->tool_id,
                // 'tool_id' => is_array($valid_tool_ids) ? implode(',', $valid_tool_ids) : ($request->tool_id ?: null),
                // 'tools' => [
                'tool_id' => $selected_tool_ids ? implode(',', $selected_tool_ids) : null,
                'tool_capacity' => $tool_capacities ? implode(',', $tool_capacities) : null,
                // ],
                'date' => $request->date,
                'time' => $matchedTime,
                'capacity' => $request->capacity,
                'tour_guide' => $request->tour_guide,
                'photographer' => $request->photographer,
                'phone_number' => $phone_number,
                'user_name' => $user_name,
                'user_id' => $user_id,
                'code' => $randomNumber,
                'total_price' => $total_price,
            ];
            // dd($bookingData);

            
            if ($activity_details->capacity >= $request->capacity) {
                // Create the booking record
                $book = Booking::create($bookingData);
                $bookingData = ['id' => $book->id] + $bookingData;
                // Update the activity's remaining capacity
                $activityData = Activity::with(['activityImages'])->find($request->activity_id);
                // if ($activityData->plan_activity == 'yes') {
                //     $activityData->update(['capacity' => $activityData->capacity - $request->capacity]);
                // } else {
                //     $activityData->update(['capacity' => $activityData->capacity - $request->capacity]);
                //     ActivityCapacity::where('activity_id', $request->activity_id)->update(['capacity' => $activityData->capacity - $request->capacity]);
                // }



            if ($activityData->plan_activity == 'yes') {
            
                $activityData->update([
                    'capacity' => $activityData->capacity - $request->capacity
                ]);
            
            } else {
            
                $activityCapacity = ActivityCapacity::where('activity_id', $request->activity_id)
                    ->where('date', $request->date)
                    ->first();
            
                if ($activityCapacity && $activityCapacity->capacity >= $request->capacity) {
            
                    $activityCapacity->update([
                        'capacity' => $activityCapacity->capacity - $request->capacity
                    ]);
            
                } else {
                    return response()->json([
                        'message' => 'Not enough capacity for this date',
                        'message_ar' => 'السعة غير كافية في هذا التاريخ'
                    ], 400);
                }
            }



                if ($user_id == 214) {
                    $book->update([
                        'status_id' => 3
                    ]);
                }
                // DB::commit();

                // Send notification
                // $this->sendBookingNotification($activity_details, $user_id, 'reservation_booked');
                $selected_tools = CommercialTool::with(['toolImages'])->whereIn('id', $selected_tool_ids)->get()->map(function ($tool) use ($selected_tool_ids, $tool_capacities) {
                    // Find the index of the tool to get matching capacity
                    $index = array_search($tool->id, $selected_tool_ids);
                    $tool->capacity = $tool_capacities[$index] ?? 0;

                    // Convert tool_images to an indexed array
                    $tool->tool_images = $tool->toolImages->map(function ($image) {
                        $image->image_path = env('APP_URL') . 'storage/app/public/' . $image->image_path;
                        return $image;
                    })->values();  // Ensure tool_images is an indexed array

                    return $tool;
                });
                // dd($book->id);
                // $this->sendBookingNotification($activity_details, $book->id, $activity_details->user_id, 'reservation_booked');

                $notificationData = [
                    'title_en' => 'Trip Has Been Booked',
                    'title_ar' => 'تم حجز الرحلة',
                    'body_en' => 'A trip "' . $activity_details->title_en . '" has been booked.',
                    'body_ar' => "تم حجز هذه الرحلة  '{$activity_details->title_ar}' ",
                    'type' => 'reservation_booked',
                    'book_id' => $book->id,
                    'action' => 'book',
                    'send_to_all' => false,
                ];

                $pushController = new NotificationController();
                $pushController->push_welcome(new PushNotificationRequest($notificationData), $activity_details->user_id); // OLD Send to service Provider
                

                // $notificationData['user_id'] = $user_id;

                // $response = $pushController->push_welcome(
                //     new PushNotificationRequest($notificationData)
                // );

                $user_email = User::where('id', $book->user_id)->value('email');
                $user_name = User::where('id', $book->user_id)->value('name');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new BookingNotificationMail(
                        //     customerName: $user_name ?? 'Valued Customer',
                        //     activityTitle: $activity_details->title_en,
                        //     bookingStatus: $book->status ?? 'confirmed',
                        //     notificationType: 'confirmation'
                        // ));

                        sendViewEmail($user_email, __('Trip Has Been Booked'), 'emails.booking-notification', [
                            'customerName' => $user_name ?? 'Valued Customer',
                            'activityTitle' => $activity_details->title_en,
                            'bookingStatus' => $book->status ?? 'confirmed',
                            'notificationType' => 'confirmation',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send booking confirmation email: ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'message' => 'Booked Successfully.',
                    'message_ar' => 'تم الحجز بنجاح.',
                    'data' => [
                        'book_data' => $bookingData,
                        'activity' => $activity_details,
                        'selected_tools' => isset($selected_tool_ids) ? $selected_tools : null,
                    ]
                ], 201);
            } else {
                // Handle no available seats

                $notificationData = [
                    'title_en' => 'No available seats',
                    'title_ar' => 'الرحلة مكتملة العدد',
                    'body_en' => 'A trip "' . $activity_details->title_en . '" No available seats in "',
                    'body_ar' => "لا يوجد مقاعد متاحة في هذه الرحلة '{$activity_details->title_ar}' ",
                    'type' => 'no_avaliable_seats',
                    'action' => 'booking',
                    'send_to_all' => false,
                ];

                $pushController = new NotificationController();
                $pushController->pushWithoutBookId(new PushNotificationRequest($notificationData), $activity_details->user_id);

                $user_email = User::where('id', $user_id)->value('email');
                $user_name_local = User::where('id', $user_id)->value('name');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new BookingNotificationMail(
                        //     customerName: $user_name_local ?? 'Valued Customer',
                        //     activityTitle: $activity_details->title_en,
                        //     bookingStatus: 'no_seats',
                        //     notificationType: 'no_seats'
                        // ));

                        sendViewEmail($user_email, __('Trip Has No Seats'), 'emails.booking-notification', [
                            'customerName' => $user_name ?? 'Valued Customer',
                            'activityTitle' => $activity_details->title_en,
                            'bookingStatus' => 'no_seats',
                            'notificationType' => 'no_seats',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send no seats email: ' . $e->getMessage());
                    }
                }

                return response()->json([
                    'error_type' => 'capacity',
                    'message' => 'No available seats.',
                    'message_ar' => 'لا يوجد مقاعد متاحة',
                    'options' => [
                        'cancel' => 'You can cancel the booking attempt.',
                        'cancel_ar' => 'يمكنك إلغاء محاولة الحجز.',
                        'waiting_list' => 'You can add yourself to the waiting list.',
                        'waiting_list_ar' => 'يمكنك إضافة نفسك إلى قائمة الانتظار.',
                    ],
                ], 400);
            }
        }
    }

    // Helper method to get available times for single-day activities
    private function getAvailableTimes($activity)
    {
        $activityDays = explode(',', $activity->activity_days);
        $activityTimes = explode(',', $activity->activity_times);

        $availableTimes = [];
        foreach ($activityDays as $index => $day) {
            if (isset($activityTimes[$index])) {
                $date = Carbon::parse($day);
                $dayName = $date->format('l');
                $availableTimes[] = [
                    'day_name' => $dayName,
                    'time' => $activityTimes[$index],
                ];
            }
        }

        return $availableTimes;
    }

    // Helper method to generate valid dates for the next 2 years
    private function generateValidDates($available_dates, $today)
    {
        $startDate = $today->copy()->startOfMonth();
        $endDate = $today->copy()->addYears(2);  // End date is 2 years from now
        $generatedDates = [];

        while ($startDate->lte($endDate)) {
            foreach ($available_dates as $day) {
                // Check if the date is valid (e.g., February 30 is invalid)
                if (checkdate($startDate->month, $day, $startDate->year)) {
                    $date = Carbon::create($startDate->year, $startDate->month, $day);

                    // Only add dates that are in the future
                    if ($date->gte($today)) {
                        $generatedDates[] = $date->toDateString();
                        break;  // Exit the loop after adding the first valid date
                    }
                }
            }
            $startDate->addMonth();
        }

        return $generatedDates;
    }

    // Helper method to find the next available date
    private function getNextAvailableDate($generatedDates, $today)
    {
        foreach ($generatedDates as $date) {
            $parsedDate = Carbon::parse($date);
            if ($parsedDate->gt($today)) {
                return $date;
            }
        }
        return null;
    }

    // Helper method to send booking notifications
    private function sendBookingNotification($activity, $book_id, $user_id, $type)
    {
        $notificationData = [
            'title_en' => $type === 'reservation_booked' ? 'Trip Has Been Booked' : ($type === 'no_avaliable_seats' ? 'No available seats' : 'Trip is Completed'),
            'title_ar' => $type === 'reservation_booked' ? 'تم حجز رحلة' : ($type === 'no_avaliable_seats' ? 'لا يوجد مقاعد متاحة فى هذه الرحلة' : 'هذه الرحلة مكتمل العدد'),
            'body_en' => $type === 'reservation_booked' ? 'A trip "' . $activity->title_en . '"  has been booked.' : ($type === 'no_avaliable_seats' ? 'No available seats in "' . $activity->title_en . '" with id = ' . $activity->id : 'This trip  "' . $activity->title_en . '"  is FULL'),
            'body_ar' => $type === 'reservation_booked' ? "تم حجز هذه الرحلة  '{$activity->title_ar}' مع المعرف = {$activity->id}" : ($type === 'no_avaliable_seats' ? "لا يوجد مقاعد متاحة في هذه الرحلة '{$activity->title_ar}' مع المعرف = {$activity->id}" : " هذه الرحلة '{$activity->title_ar}' مع المعرف = {$activity->id} مكتمل العدد"),
            'type' => $type,
            'book_id' => $book_id ?? $activity->id,
            'action' => 'booking',
            'send_to_all' => false,
        ];
        // dd($user_id);
        $pushController = new NotificationController();
        $pushController->push_welcome(new PushNotificationRequest($notificationData), $user_id); // OLD

        // $notificationData['user_id'] = $user_id;

        // $response = $pushController->push_welcome(
        //     new PushNotificationRequest($notificationData)
        // );

        $user_email = User::where('id', $user_id)->value('email');
        $user_name_local = User::where('id', $user_id)->value('name');

        if ($user_email) {
            $notificationType = match ($type) {
                'reservation_booked' => 'confirmation',
                'no_available_seats' => 'no_seats',
                default => 'trip_completed',
            };

            try {
                // Mail::to($user_email)->send(new BookingNotificationMail(
                //     customerName: $user_name_local ?? 'Valued Customer',
                //     activityTitle: $activity->title_en,
                //     bookingStatus: $type,
                //     notificationType: $notificationType
                // ));

                sendViewEmail($user_email, __('Trip Has Been Booked'), 'emails.booking-notification', [
                    'customerName' => $user_name ?? 'Valued Customer',
                    'activityTitle' => $activity->title_en,
                    'bookingStatus' => $type,
                    'notificationType' => $notificationType,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send booking notification email: ' . $e->getMessage());
            }
        }
    }

    public function storeWaitingList(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the incoming request
            try {
                $request->validate([
                    'book_id' => 'required|exists:bookings,id',  // Ensures 'activity_id' exists in 'activities' table
                    'action' => 'required|in:1,4',  // Validates action
                ]);
            } catch (ValidationException $e) {
                // Capture validation errors and return them in the response
                return response()->json([
                    'message' => 'Validation failed.',
                    'message_ar' => 'فشل التحقق من الصحة.',
                    'errors' => $e->errors(),  // This gives an array of all validation errors
                ], 422);  // Unprocessable Entity
            }
            // Determine user data (Authenticated or provided in request)
            $user_id = auth()->id();
            $user_name = auth()->user()->name;
            $phone_number = auth()->user()->phone_number;

            // Validate phone number (for Saudi Arabia)
            if (!$phone_number) {
                return response()->json([
                    'message' => 'The phone number is not valid.',
                    'message_ar' => 'رقم الهاتف غير صالح.',
                ], 422);
            }

            // Booking
            $booking_data = Booking::where('id', $request->book_id)->first();
            $currentBookingCapacity = $booking_data->capacity;
            // dd($currentBookingCapacity);
            // Activity
            $activity = Activity::find($booking_data->activity_id);
            $activity_capacity = $activity->capacity;
            $bookedCapacity = $activity->bookings()->sum('capacity');  // Total booked capacity
            $availableCapacity = $activity_capacity - $bookedCapacity;
            // dd($availableCapacity);
            // dd($activity_capacity . ' '. $currentBookingCapacity .' '. $availableCapacity . ' '. $bookedCapacity);
            if ($currentBookingCapacity >= $availableCapacity && $request->action == 1) {
                $booking_data->update([
                    'status_id' => 1,  // 1 means "Waiting"
                ]);
                // Notification for service provider for waiting activity
                $notificationData = [
                    'title_en' => 'Trip is moved to Waiting List',
                    'title_ar' => 'تم نقل هذه الرحلة إلى قائمة الانتظار',
                    'body_en' => 'This trip  "' . $activity->title_en . '"  is moved to waiting list',
                    'body_ar' => "تم نقل هذه الرحلة '{$activity->title_ar}' إلى قائمة الانتظار",
                    'type' => 'no_avaliable_seats',
                    'action' => 'booking',
                    'send_to_all' => false,
                ];

                // dd($activity->user_id);
                // Call the push_book method from the notification controller

                $pushController = new NotificationController();  // Create an instance of the notification controller
                $pushController->push_book(new PushNotificationRequest($notificationData), $activity->user_id);
                $user_email = User::where('id', $activity->user_id)->value('email');
                $user_name = User::where('id', $activity->user_id)->value('name');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new BookingNotificationMail(
                        //     customerName: $user_name ?? 'Valued Customer',
                        //     activityTitle: $activity->title_en,
                        //     bookingStatus: 'waiting',
                        //     notificationType: 'waiting_list'
                        // ));

                        sendViewEmail($user_email, __('Added to waiting list'), 'emails.booking-notification', [
                            'customerName' => $user_name ?? 'Valued Customer',
                            'activityTitle' => $activity->title_en,
                            'bookingStatus' => 'waiting',
                            'notificationType' => 'waiting_list'
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send waiting list email: ' . $e->getMessage());
                    }
                }
            } else if (/* $availableCapacity >= $currentBookingCapacity && */ $request->action == 4) {
                // Otherwise, leave it open or any other status you prefer (assuming status_id 1 means "Pending")
                $booking_data->update([
                    'status_id' => 4,  // 4 means "Cancelled by user"
                ]);

                $notificationData = [
                    'title_en' => 'Booking is Cancelled by user',
                    'title_ar' => 'تم إلغاء هذه الرحلة بواسطة المستخدم',
                    'body_en' => 'This activity "' . $activity->title_en . '" is Cancelled by user',
                    'body_ar' => "تم إلغاء هذه الرحلة '{$activity->title_ar}' بواسطة المستخدم",
                    'type' => 'no_avaliable_seats',
                    'book_id' => $booking_data->id,
                    'action' => 'booking',
                    'send_to_all' => false,
                ];

                // dd($currentBookingCapacity >= $availableCapacity);
                // Call the push_book method from the notification controller
                $pushController = new NotificationController();  // Create an instance of the notification controller
                $pushController->push_welcome(new PushNotificationRequest($notificationData), $activity->user_id); // OLD Send to service Provider
                

                // $notificationData['user_id'] = $user_id;

                // $response = $pushController->push_welcome(
                //     new PushNotificationRequest($notificationData)
                // );

                $user_email = User::where('id', $user_id)->value('email');
                $user_name_cancel = User::where('id', $user_id)->value('name');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new BookingNotificationMail(
                        //     customerName: $user_name_cancel ?? 'Valued Customer',
                        //     activityTitle: $activity->title_en,
                        //     bookingStatus: 'cancelled',
                        //     notificationType: 'cancellation'
                        // ));

                        sendViewEmail($user_email, __('Booking Cancelled'), 'emails.booking-notification', [
                            'customerName' => $user_name_cancel ?? 'Valued Customer',
                            'activityTitle' => $activity->title_en,
                            'bookingStatus' => 'cancelled',
                            'notificationType' => 'cancellation'
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send cancellation email: ' . $e->getMessage());
                    }
                }
            }
            if ($bookedCapacity == $activity_capacity) {
                // $activity->update([
                //     'status_id' => 3,  // 3 means "Completed"
                // ]);
                // Notification for service provider for waiting activity
                $notificationData = [
                    'title_en' => 'Trip is Completed',
                    'title_ar' => 'هذه الرحلة مكتمل العدد  ',
                    'body_en' => 'This trip  "' . $activity->title_en . '" is FULL',
                    'body_ar' => " هذه الرحلة '{$activity->title_ar}' مكتمل العدد  ",
                    'type' => 'activity_completed',
                    'book_id' => $booking_data->id,
                    'action' => 'booking',
                    'send_to_all' => false,
                ];

                // dd($activity->user_id);
                // Call the push_book method from the notification controller
                $pushController = new NotificationController();  // Create an instance of the notification controller
                $pushController->push_book(new PushNotificationRequest($notificationData), $activity->user_id);
                $user_email = User::where('id', $user_id)->value('email');
                $user_name_complete = User::where('id', $user_id)->value('name');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new BookingNotificationMail(
                        //     customerName: $user_name_complete ?? 'Valued Customer',
                        //     activityTitle: $activity->title_en,
                        //     bookingStatus: 'completed',
                        //     notificationType: 'trip_completed'
                        // ));

                        sendViewEmail($user_email, __('Booking Payment Completed'), 'emails.booking-notification', [
                            'customerName' => $user_name_complete ?? 'Valued Customer',
                            'activityTitle' => $activity->title_en,
                            'bookingStatus' => 'completed',
                            'notificationType' => 'trip_completed'
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send trip completed email: ' . $e->getMessage());
                    }
                }
            }
            DB::commit();

            // Update status_id in booking table
            $updated = $booking_data->update([
                'status_id' => $request->action,
            ]);
            // dd($booking_data);
            if ($updated && $request->action == 1) {
                return response()->json([
                    'message' => 'Added to waiting list successfully.',
                    'data' => $booking_data,
                ], 201);
            } else if ($updated && $request->action == 4)
                return response()->json([
                    'message' => 'Booking is Cancelled by you.',
                    'message_ar' => 'تم لإلغاء الحجز من قبلك',
                    'data' => $booking_data,
                ], 201);
            if (auth()->check()) {
                if ($booking_data->status_id == 1) {
                    // Notification for user for waiting activity
                    $notificationData = [
                        'title_en' => 'Trip is moved to Waiting List',
                        'title_ar' => 'تم نقل هذه الرحلة إلى قائمة الانتظار',
                        'body_en' => 'This trip  "' . $activity->title_en . '"  is moved to waiting list',
                        'body_ar' => "تم نقل هذه الرحلة '{$activity->title_ar}' مع المعرف = {$request->book_id} إلى قائمة الانتظار",
                        'type' => 'no_avaliable_seats',
                        'book_id' => $booking_data->id,
                        'action' => 'booking',
                        'send_to_all' => false,
                    ];

                    // dd($activity->user_id);
                    // Call the push_book method from the notification controller
                    $pushController = new NotificationController();  // Create an instance of the notification controller
                    $pushController->push_book(new PushNotificationRequest($notificationData), $user_id);
                    $user_email = User::where('id', $user_id)->value('email');
                    $user_name_local = User::where('id', $user_id)->value('name');

                    if ($user_email) {
                        try {
                            // Mail::to($user_email)->send(new BookingNotificationMail(
                            //     customerName: $user_name_local ?? 'Valued Customer',
                            //     activityTitle: $activity->title_en,
                            //     bookingStatus: 'waiting',
                            //     notificationType: 'waiting_list'
                            // ));

                            sendViewEmail($user_email, __('Added to waiting list'), 'emails.booking-notification', [
                                'customerName' => $user_name_local ?? 'Valued Customer',
                                'activityTitle' => $activity->title_en,
                                'bookingStatus' => 'waiting',
                                'notificationType' => 'waiting_list'
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send waiting list email: ' . $e->getMessage());
                        }
                    }
                } else if ($booking_data->status_id == 4) {
                    // Notification for user for cancelled by activity
                    $notificationData = [
                        'title_en' => 'Trip is Cancelled by you',
                        'title_ar' => 'تم إلغاء هذه الرحلة من قبلك',
                        'body_en' => 'This trip "' . $activity->title_en . '"  is Cancelled',
                        'body_ar' => "تم إلغاء هذه الرحلة '{$activity->title_ar}' مع المعرف = {$request->book_id}",
                        'type' => 'no_avaliable_seats',
                        'book_id' => $booking_data->id,
                        'action' => 'booking',
                        'send_to_all' => false,
                    ];

                    // dd($activity->user_id);
                    // Call the push_book method from the notification controller
                    $pushController = new NotificationController();  // Create an instance of the notification controller
                    $pushController->push_book(new PushNotificationRequest($notificationData), $user_id);
                    $user_email = User::where('id', $user_id)->value('email');
                    $user_name_local = User::where('id', $user_id)->value('name');

                    if ($user_email) {
                        try {
                            // Mail::to($user_email)->send(new BookingNotificationMail(
                            //     customerName: $user_name_local ?? 'Valued Customer',
                            //     activityTitle: $activity->title_en,
                            //     bookingStatus: 'cancelled',
                            //     notificationType: 'cancellation'
                            // ));

                            sendViewEmail($user_email, __('Booking Cancelled'), 'emails.booking-notification', [
                                'customerName' => $user_name_local ?? 'Valued Customer',
                                'activityTitle' => $activity->title_en,
                                'bookingStatus' => 'cancelled',
                                'notificationType' => 'cancellation'
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send cancellation email: ' . $e->getMessage());
                        }
                    }
                }
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
}
