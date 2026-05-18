<?php

namespace App\Http\Controllers\User;

use App\Enums\AUserTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Http\Requests\File\FileUploadRequest;
use App\Http\Requests\GenerateOTPRequest;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Requests\RegisterCompanyRequest;
use App\Http\Requests\RegisterIndividualRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Mail\OtpMail;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Cart;
use App\Models\Cashback;
use App\Models\CommercialTool;
use App\Models\Order;
use App\Models\OTP;
use App\Models\Providesrappoverequest;
use App\Models\Room;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Wishlist;
use App\Services\File\FileService;
use App\Services\OTPService;
use App\Services\TwilioService;
use App\Services\WalletService;
use App\Utils\SendOTPSMS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PHPMailer\PHPMailer\PHPMailer;

require base_path('vendor/phpmailer/phpmailer/src/PHPMailer.php');
require base_path('vendor/phpmailer/phpmailer/src/SMTP.php');
require base_path('vendor/phpmailer/phpmailer/src/Exception.php');

class UserController extends Controller
{
    public function __construct(
        public TwilioService $twilio,
        public OTPService $service,
        public FileService $fileService
    ) {}

    // public function generateOTP(GenerateOTPRequest $request)
    // {
    //     $status = $this->service->generateOTP($request->phone_numberx);

    //     return response()->json([
    //         'message' => $status ? 'Success' : 'Failed, Try Again',
    //         'phone_number' => $request->phone_number,
    //         'otp' => $status ?  $status : null
    //     ], $status ? 200 : 400);
    // }

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

    // public function generateOTP(Request $request)
    // {
    //     // Validate the incoming phone number (ensure it's a valid Saudi number)
    //     $request->validate([
    //         'phone' => 'nullable',  // Ensure phone number is provided
    //         'email' => 'nullable',
    //     ]);

    //     // $phone = $request->input('phone');
    //     // Call the custom validation method
    //     // if (!$this->validateSaudiPhoneNumber($phone)) {
    //     //     return response()->json([
    //     //         'message' => 'The phone number is not valid.',
    //     //         'message_ar' => 'رقم الهاتف غير صالح.',
    //     //     ], 422);
    //     // }
    //     // $exist = User::where('phone_number',$phone)->first();
    //     // if (!$exist) {
    //     //     return response()->json([
    //     //         'message' => 'You need to register first',
    //     //     ], 422);
    //     // }

    //     // Normalize the phone number to include the `+` prefix
    //     // $phoneNumber = preg_match('/^\+/', $request->phone) ? $request->phone : '+' . $request->phone;

    //     // Log the normalized phone number for debugging purposes
    //     // Log::info('Validated Phone Number: ' . $phoneNumber);

    //     // Generate a 6-digit OTP
    //     $otp = rand(100000, 999999);
    //     $exist = Null;
    //     // Save the OTP in the database
    //     $otpRecord = OTP::create([
    //         'phone_number' => $request->phone,
    //         'email' => $request->email,
    //         'otp' => $otp,
    //         'created_at' => now(),
    //     ]);
    //     // if (!empty($request->phone)) {

    //     //     $checkExistance = User::where('phone_number', $request->phone)->exists();  // first time send otp in sms else send via email
    //     //     if ($checkExistance) {

    //     //         $exist = 'Exist & Phone';
    //     //     } else {
    //     //         $exist = 'Not Exist & Phone';
    //     //     }

    //     //     // Check if the OTP was successfully saved
    //     //     if ($otpRecord && $request->phone) {
    //     //         // Via Whatsapp
    //     //         $this->twilio->sendMessage(
    //     //             $request->phone,
    //     //             $otp
    //     //         );
    //     //     }
    //     //     // Return a success response (avoid exposing the OTP in production)
    //     // return response()->json([
    //     //     'message' => 'OTP Sent Successfully',
    //     //     'message_ar' => 'بنجاح (OTP) تم إرسال كلمة المرور لمرة واحدة',
    //     //     'phone_number' => $request->phone,
    //     //     // 'email' => $tomemail,
    //     //     'otp' => $otp,  // For testing only; remove in production
    //     //     'exist' => $exist,  // For testing only; remove in production
    //     // ], 200);
    //     // } else if(!empty($request->email)) {
    //     //     $checkExistance = User::where('email', $request->email)->exists();  // first time send otp in sms else send via email
    //     //     if ($checkExistance) {
    //     //         $userEmail = User::where('email', $request->email)->value('email');  // first time send otp in sms else send via email
    //     //         $exist = 'Exist & Email';
    //     //     } else {
    //     //         $exist = 'Not Exist & Email';
    //     //     }

    //     //     $tomemail = $request->email;
    //     //     $mail = new PHPMailer(true);
    //     //     // إعدادات السيرفر
    //     //     $mail->isSMTP();
    //     //     $mail->Host = 'notifications.wlmma.com';
    //     //     $mail->SMTPAuth = true;
    //     //     $mail->Username = 'noreply@notifications.wlmma.com';
    //     //     $mail->Password = 'I,Lrs63ACr~[';
    //     //     $mail->SMTPSecure = 'ssl';  // أو 'tls' لو ssl معملش
    //     //     $mail->Port = 465;

    //     //     // المرسل والمستقبل
    //     //     $mail->setFrom('noreply@notifications.wlmma.com', 'WLMMA');
    //     //     $mail->addAddress($tomemail);

    //     //     // المحتوى
    //     //     $mail->isHTML(true);
    //     //     $mail->Subject = 'OTP';
    //     //     $mail->Body = "Your OTP code is: {$otp}";

    //     //     $mail->send();
    //     //     Log::info("✅ OTP email sent successfully to {$tomemail}");
    //     //     // dd('Email sent successfully');

    //     // // Return a success response (avoid exposing the OTP in production)
    //     // return response()->json([
    //     //     'message' => 'OTP Sent Successfully',
    //     //     'message_ar' => 'بنجاح (OTP) تم إرسال كلمة المرور لمرة واحدة',
    //     //     // 'phone_number' => $request->phone,
    //     //     'email' => $tomemail,
    //     //     'otp' => $otp,  // For testing only; remove in production
    //     //     'exist' => $exist,  // For testing only; remove in production
    //     // ], 200);
    //     // }

    //     if (!empty($request->phone)) {
    //         // Check if user exists by phone
    //         $exists = User::where('phone_number', $request->phone)->exists();
    //         $exist = $exists ? 'Exist & Phone' : 'Not Exist & Phone';

    //         // Send OTP via WhatsApp
    //         if ($otpRecord) {
    //             $this->twilio->sendMessage(
    //                 $request->phone,
    //                 $otp
    //             );
    //         }

    //         return response()->json([
    //             'message' => 'OTP Sent Successfully',
    //             'message_ar' => 'بنجاح (OTP) تم إرسال كلمة المرور لمرة واحدة',
    //             'phone_number' => $request->phone,
    //             'otp' => $otp,  // ⚠️ remove in production
    //             'exist' => $exist,  // ⚠️ remove in production
    //         ], 200);
    //     } elseif (!empty($request->email)) {
    //         // Check if user exists by email
    //         $exists = User::where('email', $request->email)->exists();
    //         $exist = $exists ? 'Exist & Email' : 'Not Exist & Email';

    //         $toEmail = $request->email;

    //         $mail = new PHPMailer(true);

    //         // SMTP settings
    //         $mail->isSMTP();
    //         $mail->Host = 'notifications.wlmma.com';
    //         $mail->SMTPAuth = true;
    //         $mail->Username = 'noreply@notifications.wlmma.com';
    //         $mail->Password = env('MAIL_PASSWORD');  // ✅ move to .env
    //         $mail->SMTPSecure = 'ssl';
    //         $mail->Port = 465;

    //         // Sender & receiver
    //         $mail->setFrom('noreply@notifications.wlmma.com', 'WLMMA');
    //         $mail->addAddress($toEmail);

    //         // Content
    //         $mail->isHTML(true);
    //         $mail->Subject = 'OTP';
    //         $mail->Body = "Your OTP code is: <b>{$otp}</b>";

    //         $mail->send();
    //         Log::info("✅ OTP email sent successfully to {$toEmail}");

    //         return response()->json([
    //             'message' => 'OTP Sent Successfully',
    //             'message_ar' => 'بنجاح (OTP) تم إرسال كلمة المرور لمرة واحدة',
    //             'email' => $toEmail,
    //             'otp' => $otp,  // ⚠️ remove in production
    //             'exist' => $exist,  // ⚠️ remove in production
    //         ], 200);
    //     }

    //     // If OTP creation fails, return a failure response
    //     // return response()->json([
    //     //     'message' => 'Failed to generate OTP, Please try again.',
    //     //     'message_ar' => 'فشل إنشاء OTP، يرجى المحاولة مرة أخرى.',
    //     //     'phone_number' => $request->phone,
    //     //     'otp' => null,
    //     // ], 400);
    // }

    public function generateOTP(Request $request)
    {
        // 1️⃣ Validation: لازم phone أو email واحد فيهم
        $request->validate([
            'phone' => 'nullable|required_without:email|string',
            'email' => 'nullable|required_without:phone|email',
        ]);

        $email = $request->email;

        // 2️⃣ Generate OTP
        $otp = random_int(100000, 999999);

        // 3️⃣ Check existence
        if ($request->filled('phone')) {
            $exists = User::where('phone_number', $request->phone)->exists();
            $exist = $exists ? 'Exist & Phone' : 'Not Exist & Phone';
            $is_deactive = User::where('phone_number', $request->phone)->first()->is_deactive;
            if ($is_deactive == 1) {
                return response()->json([
                    'message' => 'You are deactivated',
                    // 'message_ar' => 'فشل إنشاء رمز التحقق',
                ], 401);
            }
        } else {
            $user = User::where('email', $email)->first();

            $exist = $user ? 'Exist & Email' : 'Not Exist & Email';

            if ($user && $user->is_deactive == 1) {
                return response()->json([
                    'message' => 'You are deactivated',
                ], 401);
            }
        }

        if ($email == 'test@wlmma.com') {
            $otp = (int) '452573';
        }

        // 4️⃣ Save OTP
        $otpRecord = OTP::create([
            'phone_number' => $request->phone,
            'email' => $email,
            'otp' => $otp,
        ]);

        if (!$otpRecord) {
            return response()->json([
                'message' => 'Failed to generate OTP',
                'message_ar' => 'فشل إنشاء رمز التحقق',
            ], 500);
        }

        // 5️⃣ Send OTP
        try {
            // 📲 Phone (WhatsApp)
            if ($request->filled('phone')) {
                $this->twilio->sendMessage(
                    $request->phone,
                    $otp
                );

                return response()->json([
                    'message' => 'OTP Sent Successfully',
                    'message_ar' => 'بنجاح (OTP) تم إرسال كلمة المرور لمرة واحدة',
                    'phone_number' => $request->phone,
                    'exist' => $exist,
                    'otp' => $otp,  // ❌ remove in production
                ], 200);
            }

            // 📧 Email
            if ($request->filled('email')) {
                try {
                    // Mail::to($request->email)->send(new OtpMail($otp)); // Nady
                    // sendEmail($request->email, __('OTP Verification'), 'emails.otp', [
                    //     'otp' => $otp,
                    // ]);

                    sendViewEmail(
                        $request->email,
                        __('OTP Verification'),
                        'emails.otp',
                        [
                            'otp' => $otp,
                        ]
                    );

                    return response()->json([
                        'message' => 'OTP Sent Successfully',
                        'message_ar' => 'بنجاح (OTP) تم إرسال كلمة المرور لمرة واحدة',
                        'email' => $request->email,
                        'exist' => $exist,
                        'otp' => $otp,  // ❌ remove in production
                    ], 200);

                    Log::info("OTP email sent to {$request->email}");
                } catch (\Exception $e) {
                    Log::error('Failed to send OTP email: ' . $e->getMessage());
                }

                return response()->json([
                    'message' => 'OTP Sent Successfully',
                    'message_ar' => 'بنجاح (OTP) تم إرسال كلمة المرور لمرة واحدة',
                    'email' => $request->email,
                    'exist' => $exist,
                    'otp' => $otp,  // ❌ remove in production
                ], 200);
            }
        } catch (\Throwable $e) {
            Log::error('OTP Send Failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to send OTP',
                'message_ar' => 'فشل إرسال رمز التحقق',
            ], 500);
        }
    }

    // public function verifyOTP(VerifyOTPRequest $request)
    // {
    //     $user = $this->service->validateOTP($request->phone_number, $request->otp);
    //     if ($user) {

    //         return response()->json([
    //             'data' => [
    //                 'message' => 'Success',
    //                 'needs_to_register' => $user->name ? false : true,
    //                 'user_type' => $user->name ? $user->userType->type : null,
    //                 'data' => $user->name ? $user : null,
    //             ],
    //             'meta' => [
    //                 'token' => $user->createToken($request->phone_number)->plainTextToken
    //             ]
    //         ], 200);
    //     }
    //      else {
    //         return response()->json([
    //             'data' => [
    //                 'message' => 'False'
    //             ],
    //             'meta' => [
    //                 'token' => $user
    //             ]
    //         ], 401);
    //     }
    // }
    public function verifyOTP(Request $request)
    {
        // Validate the request
        $request->validate([
            'otp' => 'required|numeric',  // Ensure OTP is numeric
            'phone' => 'nullable|string',  // Ensure phone number is provided
            'email' => 'nullable|email',
            'country_code' => 'nullable',
            'fcm_token' => 'nullable'
        ]);

        $phone = $request->phone;
        $email = $request->email;
        $otp = $request->otp;

        if ($email == 'test@wlmma.com') {
            $otp = 452573;
        }

        // Custom validation for Saudi phone numbers
        // if (!$this->validateSaudiPhoneNumber($phone)) {
        //     return response()->json([
        //         'message' => 'The phone number is not valid.',
        //         'message_ar' => 'رقم الهاتف غير صالح.',
        //     ], 422);
        // }

        //  $exist = User::where('phone_number',$phone)->first();
        // if (!$exist) {
        //     return response()->json([
        //         'message' => 'You need to register first',
        //     ], 422);
        // }

        // Normalize the phone number to include the '+' prefix
        // $phoneNumber = preg_match('/^\+/', $phone) ? $phone : '+' . $phone;

        // Retrieve OTP record (valid for 5 minutes)
        $otpRecord = OTP::where([
            ['phone_number', $phone],
            ['email', $email],
            ['otp', $otp],
            ['created_at', '>', now()->subMinutes(5)]
        ])->first();

        if (!$otpRecord) {
            // OTP is invalid or expired
            return response()->json([
                'data' => [
                    'message' => 'Invalid or expired OTP.',
                    'message_ar' => ' كلمة المرور لمرة واحدة غير صالحة أو منتهية الصلاحية (OTP) .',
                ],
                'meta' => [
                    'token' => null
                ]
            ], 401);
        }

        // Delete OTP after successful verification
        if ($phone) {
            OTP::where('phone_number', $phone)->delete();
            $user = User::where('phone_number', $phone)->first();
        }
        if ($email) {
            if ($email != 'test@wlmma.com') {
                OTP::where('email', $email)->delete();
            }
            $user = User::where('email', $email)->first();
        }

        // Retrieve or create the user associated with the phone number
        if (!$user) {
            $user = User::create([
                'phone_number' => $phone,
                'country_code' => $request->country_code,
                'email' => $request->email,
                'fcm_token' => $request->fcm_token,
            ]);
        } else {
            $user->update([
                'fcm_token' => $request->fcm_token,
            ]);
        }
        if ($user->live_photo) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name = DB::table('files')->find($user->live_photo)->name;
            // Retrieve the user's profile image if available
            $profileImage = null;
            // Assuming the image is stored in 'storage/app/public/activities'
            $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $profileImage = null;
        }

        if ($user->user_types_id == 1) {
            // For Customer
            $activity_numbers = DB::table('bookings')->where('user_id', $user->id)->count();
        } else {
            // For Service Provider (Company / Prerson)
            $activity_numbers = DB::table('activities')->where('user_id', $user->id)->count();
        }

        // Add custom attributes to the user object
        if ($user->name) {
            $user->live_photo = $profileImage;  // Add live_photo to the user object
            $user->travel_trips = $activity_numbers;  // Add travel_trips to the user object
        }
        $forToken = $email ? $email : $phone;
        return response()->json([
            'data' => [
                'message' => 'Success',
                'needs_to_register' => $user->name ? false : true,
                'user_type' => $user->name ? $user->userType->type : null,
                'user' => $user->name ? $user : null,  // User object now includes live_photo and travel_trips
            ],
            'meta' => [
                'token' => $user->createToken($forToken)->plainTextToken
            ]
        ], 200);
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('code', $code)->exists());

        return $code;
    }

    // Customer
    public function registerUser(RegisterUserRequest $request)  // Customer
    {
        $request->validate([
            'name' => [
                'nullable',
                'string',
                'min:3',
                'max:25'
            ],
            'gender' => [
                'required',
                'string',
                'string',
                Rule::in(['male', 'female']),  // accepted values
            ],
        ]);

        if ($request->code) {
            $codeUser = User::where('code', $request->code)->first();

            if ($codeUser) {
                $walletUser = Wallet::where('user_id', $codeUser->id)->first();
                $code_cashback = Cashback::first()->code_cashback;
                // dd($code_cashback);
                if ($walletUser) {
                    $walletUser->balance += $code_cashback;  // ✅ increment by 20
                    $walletUser->save();
                }
                // dd($walletUser);
            }
        }

        $request->validate([
            // 'otp' => 'required|numeric',  // Ensure OTP is numeric
            // 'phone' => 'nullable',  // Ensure phone number is provided
            // 'email' => 'nullable|email|unique:users,email',
            'phone' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone_number')->ignore(auth()->id()),  // ignore current user
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore(auth()->id()),  // ignore current user
            ],
            // 'otp' => 'required_with:phone,email|string',
            // 'country_code' => 'nullable',
            // 'fcm_token' => 'nullable'
        ]);

        // Upload profile image (if provided)
        $livePhoto = null;
        if ($request->hasFile('live_photo')) {
            $livePhoto = $this->fileService->upload($request->file('live_photo'));
        }

        $loggedUser = Auth::user();

        $user = $request->user();
        // if (!$user->name) {
        //     $user->name = $request->name;
        // }

        $user->name = $request->name;
        $user->last_name = $request->last_name;
        $user->acting_as = 'customer';
        $user->gender = $request->gender;
        $user->country_code = $request->country_code;
        $user->code = $this->generateUniqueCode();

        if (!empty($user->phone_number) && $request->phone != $user->phone_number && !empty($request->phone)) {
            // dd("User Phone -> ". $user->phone_number. " Requested Phone -> ".$request->phone);

            // dd($user->phone_number);
            $otpRecord = OTP::where('user_id', $user->id)
                // ->where('phone_number', $request->phone_number)
                ->where('created_at', '>', now()->subMinutes(5))
                ->latest()
                ->first();
            // dd($otpRecord);

            if (!$otpRecord || $otpRecord->otp !== $request->otp) {
                return response()->json([
                    'status' => 'error',
                    'type' => 'phone',
                    'message' => 'Invalid or reqiered OTP.',
                    'message_ar' => 'رمز التحقق مطلوب أو غير صحيح.',
                ], 400);
            }
        }
        $user->phone_number = $request->phone ? $request->phone : $user->phone;
        if (!empty($user->email) && $request->email != $user->email && !empty($request->email)) {
            // dd("User Email -> ". $user->email. " Requested Email -> ".$request->email);
            $otpRecord = OTP::where('user_id', $user->id)
                // ->where('phone_number', $request->phone_number)
                ->where('created_at', '>', now()->subMinutes(5))
                ->latest()
                ->first();
            // dd($otpRecord);

            if (!$otpRecord || $otpRecord->otp !== $request->otp) {
                return response()->json([
                    'status' => 'error',
                    'type' => 'email',
                    'message' => 'Invalid or reqiered OTP.',
                    'message_ar' => 'رمز التحقق مطلوب أو غير صحيح.',
                ], 400);
            }
        }
        $user->email = $request->email ? $request->email : $user->email;

        $user->user_types_id = AUserTypeEnum::USER;
        $user->live_photo = $livePhoto['id'] ?? null;  // Assign uploaded photo ID or null
        $user->save();

        $activity_numbers = DB::table('bookings')->where('user_id', $request->user()->id)->count();
        if ($user->live_photo) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name = DB::table('files')->find($user->live_photo)->name;
            // Retrieve the user's profile image if available
            $profileImage = null;
            // Assuming the image is stored in 'storage/app/public/activities'
            $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $profileImage = null;
        }

        if ($user->name) {
            $user->live_photo = $profileImage;  // Add live_photo to the user object
            $user->travel_trips = $activity_numbers;  // Add travel_trips to the user object
        }

        // Create empty cart
        $cart = Cart::firstOrCreate(
            ['user_id' => auth()->id()],
            ['total_price' => 0]
        );
        $wallet = Wallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );
        $order = Order::firstOrCreate(
            ['user_id' => auth()->id()],
            ['total' => 0],
            ['status' => null]
        );
        // return response()->json([
        //     'data' => [
        //         'message' => 'Success',
        //         'user_type' => $user->name ? $user->userType->type : null,
        //         'data' => $user->name ? $user : null,
        //         'travel_trips' => $activity_numbers, // Include the profile image URL
        //     ]
        // ], 200);
        // dd($user->id);
        // if($user->phone_number){
        //     if ($request->phone != $user->phone_number && !empty($request->phone)) {
        //         dd("User Phone -> ". $user->phone_number. "Requested Phone -> ".$request->phone);

        //         // dd($user->phone_number);
        //         $otpRecord = OTP::where('user_id', $user->id)
        //             // ->where('phone_number', $request->phone_number)
        //             ->where('created_at', '>', now()->subMinutes(5))
        //             ->latest()
        //             ->first();
        //         // dd($otpRecord);

        //         if (!$otpRecord || $otpRecord->otp !== $request->otp) {
        //             return response()->json([
        //                 'status' => 'error',
        //                 'type' => 'phone',
        //                 'message' => 'Invalid or reqiered OTP.',
        //                 'message_ar' => 'رمز التحقق مطلوب أو غير صحيح.',
        //             ], 400);
        //         }
        //     }
        // }

        // dd($request->email.' / '.$user->email);
        // if($user->email){
        //     if ($request->email != $user->email && !empty($request->email)) {
        //     dd("User Email -> ". $user->email. "Requested Email -> ".$request->email);
        //     $otpRecord = OTP::where('user_id', $user->id)
        //         // ->where('phone_number', $request->phone_number)
        //         ->where('created_at', '>', now()->subMinutes(5))
        //         ->latest()
        //         ->first();
        //     // dd($otpRecord);

        //     if (!$otpRecord || $otpRecord->otp !== $request->otp) {
        //         return response()->json([
        //             'status' => 'error',
        //             'type' => 'email',
        //             'message' => 'Invalid or reqiered OTP.',
        //             'message_ar' => 'رمز التحقق مطلوب أو غير صحيح.',
        //         ], 400);
        //     }
        // }
        // }

        // $notificationData = [
        //     'title_en' => 'Welcome to Wlmma',
        //     'title_ar' => ' أهلا بك فى ولمة',
        //     'body_en' => 'We’re excited to have you on board! Explore the app’s features and enjoy a unique experience tailored to your needs. If you need any assistance, our support team is always ready to help. Enjoy your time!',
        //     'body_ar' => 'نحن سعداء بانضمامك إلينا! اكتشف ميزات التطبيق واستمتع بتجربة مميزة تلبي احتياجاتك. إذا كنت بحاجة إلى أي مساعدة، فريق الدعم دائمًا جاهز لخدمتك.نتمنى لك وقتًا ممتعًا!',
        //     'type' => 'welcome',
        //     'book_id' => null,
        //     'action' => 'welcome',
        //     'send_to_all' => false,
        // ];

        // // dd($activity->user_id);
        // // Call the push_book method from the notification controller
        // $pushController = new NotificationController();  // Create an instance of the notification controller
        // $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id);

        // $user_email = User::where('phone_number', $request->phone)->value('email');
        $user_email = $request->email;

        // $tomemail = 'growdigitalsarah@gmail.com';
        // Check if the OTP was successfully saved
        // if ($user_email) {
        //     $tomemail = $user_email;
        //     // dd($tomemail);
        //     // Log OTP generation (avoid logging OTP in production)
        //     // Log::info('OTP Generated: ' . $otp . ' for Phone Number: ' . $phoneNumber);
        //     // try {
        //     $mail = new PHPMailer(true);
        //     // إعدادات السيرفر
        //     $mail->isSMTP();
        //     $mail->Host = 'notifications.wlmma.com';
        //     $mail->SMTPAuth = true;
        //     $mail->Username = 'noreply@notifications.wlmma.com';
        //     $mail->Password = 'I,Lrs63ACr~[';
        //     $mail->SMTPSecure = 'ssl';  // أو 'tls' لو ssl معملش
        //     $mail->Port = 465;

        //     // المرسل والمستقبل
        //     $mail->setFrom('noreply@notifications.wlmma.com', 'WLMMA');
        //     $mail->addAddress($tomemail);

        //     // المحتوى
        //     $mail->isHTML(true);
        //     $mail->Subject = 'OTP';
        //     $mail->Body = "Your OTP code is: {$otp}";

        //     $mail->send();
        //     // Log::info("✅ OTP email sent successfully to {$tomemail}");
        //     // dd('Email sent successfully');
        //     // } catch (\Exception $e) {
        //     //     dd('Mail error: ' . $e->getMessage());
        //     // }
        // }
        // dd(Wallet::where('user_id', $user->id)->first()->balance);
        return response()->json([
            'data' => [
                'message' => 'Success',
                'needs_to_register' => $user->name ? false : true,
                'user_type' => $user->name ? $user->userType->type : null,
                'user' => $user->name ? $user : null,  // User object now includes live_photo and travel_trips
                // 'wallet' => Wallet::where('user_id', $user->id)->first()->balance,
            ],
        ], 200);
    }

    // Providers
    public function registerCompany(RegisterCompanyRequest $request)
    {
        if ($request->code) {
            $codeUser = User::where('code', $request->code)->first();
            $code_cashback = Cashback::first()->code_cashback;
            if ($codeUser) {
                $walletUser = Wallet::where('user_id', $codeUser->id)->first();

                if ($walletUser) {
                    $walletUser->balance += $code_cashback;  // ✅ increment by 20
                    $walletUser->save();
                }
            }
        }

        $user = $request->user();

        if ($request->hasFile('trn_image')) {
            $trn_image = $this->fileService->upload($request->file('trn_image'));
        }
        if ($request->hasFile('company_logo')) {
            $company_logo = $this->fileService->upload($request->file('company_logo'));
        }
        if ($request->hasFile('iban_image')) {
            $iban_image = $this->fileService->upload($request->file('iban_image'));
        }
        if ($request->hasFile('cr_image')) {
            $cr_image = $this->fileService->upload($request->file('cr_image'));
        }

        // dd($user);
        // if (!$user->name) {
        //     $user->name = $request->name;
        //     $user->last_name = $request->last_name;
        // }

        $user->name = $request->name;
        $user->last_name = $request->last_name;
        $user->acting_as = 'customer';
        $user->user_types_id = AUserTypeEnum::COMPANY;
        $user->phone_number = $user->phone_number;
        $user->iban = $request->IBAN;
        $user->iban_image = $iban_image['id'];
        $user->trn = $request->TRN;
        $user->trn_image = $trn_image['id'];
        $user->company_logo = $company_logo['id'];
        $user->cr = $request->CR;
        $user->cr_image = $cr_image['id'];
        $user->code = $this->generateUniqueCode();
        $user->save();
        Providesrappoverequest::create([
            'customer_id' => $user->id
        ]);
        // $user = User::create([
        //     'name' => $request->name,
        //     'user_types_id' => AUserTypeEnum::COMPANY,
        //     'IBAN' => $request->IBAN,
        //     // 'IBAN' => $request->IBAN,
        //     'TRN' => $request->TRN,
        //     'CR' => $request->CR,
        // ]);

        // iban_image
        if ($user->iban_image) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name = DB::table('files')->find($user->iban_image)->name;
            // Retrieve the user's profile image if available
            $iban_image = null;
            // Assuming the image is stored in 'storage'
            $iban_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $iban_image = null;
        }

        // cr_image
        if ($user->cr_image) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name = DB::table('files')->find($user->cr_image)->name;
            // Retrieve the user's profile image if available
            $cr_image = null;
            // Assuming the image is stored in 'storage'
            $cr_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $cr_image = null;
        }

        // trn_image
        if ($user->trn_image) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name = DB::table('files')->find($user->trn_image)->name;
            // Retrieve the user's profile image if available
            $trn_image = null;
            // Assuming the image is stored in 'storage'
            $trn_image = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $trn_image = null;
        }

        // company_logo
        if ($user->company_logo) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name2 = DB::table('files')->find($user->company_logo)->name;
            // Retrieve the user's company_logo if available
            $company_logo = null;
            // Assuming the image is stored in 'storage/'
            $company_logo = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
        } else {
            $company_logo = null;
        }

        if ($user->name) {
            $user->trn_image = $trn_image;  // Add live_photo to the user object
            $user->company_logo = $company_logo;  // Add company_logo to the user object
            $user->cr_image = $cr_image;  // Add cr_image to the user object
            $user->iban_image = $iban_image;  // Add iban_image to the user object

            $checkCampaign = Cashback::value('campaign');  // ✅ directly returns the value of 'campaign'

            if ($checkCampaign === 'yes') {
                $walletUser = Wallet::where('user_id', auth()->id())->first();

                if ($walletUser) {
                    $walletUser->balance += $campaign_amount;  // ✅ increment by 20
                    $walletUser->save();
                }
            }
        }

        // Create empty cart
        $cart = Cart::firstOrCreate(
            ['user_id' => auth()->id()],
            ['total_price' => 0]
        );
        $wallet = Wallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );

        $order = Order::firstOrCreate(
            ['user_id' => auth()->id()],
            ['total' => 0],
            ['status' => null]
        );

        // $notificationData = [
        //     'title_en' => 'Welcome to Wlmma',
        //     'title_ar' => ' أهلا بك فى ولمة',
        //     'body_en' => 'We’re excited to have you on board! Explore the app’s features and enjoy a unique experience tailored to your needs. If you need any assistance, our support team is always ready to help. Enjoy your time!',
        //     'body_ar' => 'نحن سعداء بانضمامك إلينا! اكتشف ميزات التطبيق واستمتع بتجربة مميزة تلبي احتياجاتك. إذا كنت بحاجة إلى أي مساعدة، فريق الدعم دائمًا جاهز لخدمتك.نتمنى لك وقتًا ممتعًا!',
        //     'type' => 'welcome',
        //     'book_id' => null,
        //     'action' => 'welcome',
        //     'send_to_all' => false,
        // ];

        // // dd($activity->user_id);
        // // Call the push_book method from the notification controller
        // $pushController = new NotificationController();  // Create an instance of the notification controller
        // $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id);

        $checkCampaign = Cashback::value('campaign');  // ✅ directly returns the value of 'campaign'

        if ($checkCampaign === 'yes') {
            $walletUser = Wallet::where('user_id', auth()->id())->first();
            $code_cashback = Cashback::first()->code_cashback;
            if ($walletUser) {
                $walletUser->balance += $code_cashback;  // ✅ increment by 20
                $walletUser->save();
            }
        }

        // $user_email = User::where('phone_number', $request->phone)->value('email');

        // // $tomemail = 'growdigitalsarah@gmail.com';
        // // Check if the OTP was successfully saved
        // if ($user_email) {
        //     $tomemail = $user_email;
        //     // dd($tomemail);
        //     // Log OTP generation (avoid logging OTP in production)
        //     // Log::info('OTP Generated: ' . $otp . ' for Phone Number: ' . $phoneNumber);
        //     // try {
        //     $mail = new PHPMailer(true);
        //     // إعدادات السيرفر
        //     $mail->isSMTP();
        //     $mail->Host = 'notifications.wlmma.com';
        //     $mail->SMTPAuth = true;
        //     $mail->Username = 'noreply@notifications.wlmma.com';
        //     $mail->Password = 'I,Lrs63ACr~[';
        //     $mail->SMTPSecure = 'ssl';  // أو 'tls' لو ssl معملش
        //     $mail->Port = 465;

        //     // المرسل والمستقبل
        //     $mail->setFrom('noreply@notifications.wlmma.com', 'WLMMA');
        //     $mail->addAddress($tomemail);

        //     // المحتوى
        //     $mail->isHTML(true);
        //     $mail->Subject = 'OTP';
        //     $mail->Body = "Your OTP code is: {$otp}";

        //     $mail->send();
        //     // Log::info("✅ OTP email sent successfully to {$tomemail}");
        //     // dd('Email sent successfully');
        //     // } catch (\Exception $e) {
        //     //     dd('Mail error: ' . $e->getMessage());
        //     // }
        // }
        return response()->json([
            'data' => [
                'message' => 'Success',
                'user_type' => $user->name ? $user->userType->type : null,
                'user' => $user->name ? $user : null,
            ]
        ], 200);
    }

    public function registerIndividual(RegisterIndividualRequest $request)
    {
        $request->validate([
            'gender' => [
                'required',
                'string',
                'string',
                Rule::in(['male', 'female']),  // accepted values
            ],
        ]);

        if ($request->code) {
            $codeUser = User::where('code', $request->code)->first();

            if ($codeUser) {
                $walletUser = Wallet::where('user_id', $codeUser->id)->first();
                $code_cashback = Cashback::first()->code_cashback;
                if ($walletUser) {
                    $walletUser->balance += $code_cashback;  // ✅ increment by 20
                    $walletUser->save();
                }
            }
        }

        // Handle the live photo upload
        if ($request->hasFile('live_photo')) {
            $live_photo = $this->fileService->upload($request->file('live_photo'));
        }

        if ($request->hasFile('national_id_image')) {
            $national_id_image = $this->fileService->upload($request->file('national_id_image'));
        }
        if ($request->hasFile('iban_image')) {
            $iban_image = $this->fileService->upload($request->file('iban_image'));
        }

        // Update the user's information
        $user = $request->user();
        // if (!$user->name) {
        //     $user->name = $request->name;
        // }
        $user->name = $request->name;
        $user->last_name = $request->last_name;
        $user->acting_as = 'customer';
        $user->gender = $request->gender;
        $user->user_types_id = AUserTypeEnum::INDIVIDUAL_BUSINESS;
        $user->iban = $request->IBAN;
        $user->iban_image = $iban_image['id'];
        $user->national_id = $request->national_id;
        $user->tour_guide = $request->tour_guide;
        $user->phone_number = $user->phone_number;
        $user->live_photo = $live_photo['id'];
        // $user->national_id_image = env('APP_URL') . 'storage/app/public/' . $national_id_image;
        $user->national_id_image = $national_id_image['id'];
        $user->code = $this->generateUniqueCode();
        $user->save();

        Providesrappoverequest::create([
            'customer_id' => $user->id
        ]);

        // live_photo image
        if ($user->live_photo) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name = DB::table('files')->find($user->live_photo)->name;
            // Retrieve the user's profile image if available
            $profileImage = null;
            // Assuming the image is stored in 'storage/app/public/activities'
            $profileImage = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        } else {
            $profileImage = null;
        }

        // iban_image
        if ($user->iban_image) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name2 = DB::table('files')->find($user->iban_image)->name;
            // Retrieve the user's iban_image if available
            $iban_image = null;
            // Assuming the image is stored in 'storage/'
            $iban_image = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
        } else {
            $iban_image = null;
        }

        // national_id_image
        if ($user->national_id_image) {
            // $path = DB::table('files')->find($user->live_photo)->path;
            $image_name2 = DB::table('files')->find($user->national_id_image)->name;
            // Retrieve the user's national_id_image if available
            $national_id_image = null;
            // Assuming the image is stored in 'storage/'
            $national_id_image = env('APP_URL') . 'storage/' . $image_name2 ? env('APP_URL') . 'storage/' . $image_name2 : null;
        } else {
            $national_id_image = null;
        }

        if ($user->name) {
            $user->live_photo = $profileImage;  // Add live_photo to the user object
            $user->national_id_image = $national_id_image;  // Add travel_trips to the user object
            $user->iban_image = $iban_image;  // Add iban_image to the user object
        }

        // dd($national_id_image);

        // Create empty cart
        $cart = Cart::firstOrCreate(
            ['user_id' => auth()->id()],
            ['total_price' => 0]
        );
        $wallet = Wallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );

        $order = Order::firstOrCreate(
            ['user_id' => auth()->id()],
            ['total' => 0],
            ['status' => null]
        );

        $checkCampaign = Cashback::value('campaign');  // ✅ directly returns the value of 'campaign'

        if ($checkCampaign === 'yes') {
            $walletUser = Wallet::where('user_id', auth()->id())->first();
            $code_cashback = Cashback::first()->code_cashback;
            if ($walletUser) {
                $walletUser->balance += $code_cashback;  // ✅ increment by 20
                $walletUser->save();
            }
        }

        // $notificationData = [
        //     'title_en' => 'Welcome to Wlmma',
        //     'title_ar' => ' أهلا بك فى ولمة',
        //     'body_en' => 'We’re excited to have you on board! Explore the app’s features and enjoy a unique experience tailored to your needs. If you need any assistance, our support team is always ready to help. Enjoy your time!',
        //     'body_ar' => 'نحن سعداء بانضمامك إلينا! اكتشف ميزات التطبيق واستمتع بتجربة مميزة تلبي احتياجاتك. إذا كنت بحاجة إلى أي مساعدة، فريق الدعم دائمًا جاهز لخدمتك.نتمنى لك وقتًا ممتعًا!',
        //     'type' => 'welcome',
        //     'book_id' => null,
        //     'action' => 'welcome',
        //     'send_to_all' => false,
        // ];

        // // dd($activity->user_id);
        // // Call the push_book method from the notification controller
        // $pushController = new NotificationController();  // Create an instance of the notification controller
        // $pushController->push_welcome(new PushNotificationRequest($notificationData), $user->id);

        $user_email = User::where('phone_number', $request->phone)->value('email');

        // $tomemail = 'growdigitalsarah@gmail.com';
        // Check if the OTP was successfully saved
        // if ($user_email) {
        //     $tomemail = $user_email;
        //     // dd($tomemail);
        //     // Log OTP generation (avoid logging OTP in production)
        //     // Log::info('OTP Generated: ' . $otp . ' for Phone Number: ' . $phoneNumber);
        //     // try {
        //     $mail = new PHPMailer(true);
        //     // إعدادات السيرفر
        //     $mail->isSMTP();
        //     $mail->Host = 'notifications.wlmma.com';
        //     $mail->SMTPAuth = true;
        //     $mail->Username = 'noreply@notifications.wlmma.com';
        //     $mail->Password = 'I,Lrs63ACr~[';
        //     $mail->SMTPSecure = 'ssl';  // أو 'tls' لو ssl معملش
        //     $mail->Port = 465;

        //     // المرسل والمستقبل
        //     $mail->setFrom('noreply@notifications.wlmma.com', 'WLMMA');
        //     $mail->addAddress($tomemail);

        //     // المحتوى
        //     $mail->isHTML(true);
        //     $mail->Subject = 'OTP';
        //     $mail->Body = "Your OTP code is: {$otp}";

        //     $mail->send();
        //     // Log::info("✅ OTP email sent successfully to {$tomemail}");
        //     // dd('Email sent successfully');
        //     // } catch (\Exception $e) {
        //     //     dd('Mail error: ' . $e->getMessage());
        //     // }
        // }

        return response()->json([
            'data' => [
                'message' => 'Success',
                'user_type' => $user->name ? $user->userType->type : null,
                'user' => $user->name ? $user : null,
            ]
        ], 200);
    }

    public function upgradeToProvider(Request $request)
    {
        $user = $request->user();
        $userType = $request->user_types_id;

        if ($userType == 3) {  // Individual Business

            $national_id_image = $request->hasFile('national_id_image')
                ? $this->fileService->upload($request->file('national_id_image'))
                : null;

            // Update the current user
            $user->update([
                'acting_as' => 'customer',
                'iban' => $request->IBAN,
                'national_id' => $request->national_id,
                'national_id_image' => $national_id_image['id'] ?? null,  // ✅ fix here
                'tour_guide' => $request->tour_guide,
                'user_types_id' => $userType,
            ]);
            Providesrappoverequest::create([
                'customer_id' => $user->id
            ]);

            // Refresh the user instance to get updated data
            $newUser = $user->fresh();

            // Resolve image URLs safely

            $nationalIdFile = $newUser->national_id_image
                ? DB::table('files')->find($newUser->national_id_image)
                : null;

            $newUser->national_id_image = $nationalIdFile
                ? env('APP_URL') . 'storage/' . $nationalIdFile->name
                : null;

            $liveFile = $newUser->live_photo
                ? DB::table('files')->find($newUser->live_photo)->name
                : null;

            $newUser->live_photo = $liveFile
                ? env('APP_URL') . 'storage/' . $liveFile
                : null;

            // Send welcome notification
            $notificationData = [
                'title_en' => 'Your service provider request is under review. We will notify you once it’s approved.',
                'title_ar' => 'طلبكم من مزود الخدمة قيد المراجعة. سنُعلمكم فور الموافقة عليه.',
                'body_en' => 'Your service provider request is under review. We will notify you once it’s approved.',
                'body_ar' => 'طلبكم من مزود الخدمة قيد المراجعة. سنُعلمكم فور الموافقة عليه.',
                'type' => 'welcome',
                'book_id' => null,
                'action' => 'welcome',
                'send_to_all' => false,
            ];

            (new NotificationController())->push_welcome(new PushNotificationRequest($notificationData), $user->id);  // OLD

            // $pushController = new NotificationController();
            // $notificationData['user_id'] = $user->id;

            // $response = $pushController->push_welcome(
            //     new PushNotificationRequest($notificationData)
            // );

            $user_email = User::where('phone_number', $request->phone)->value('email');

            // if ($user_email) {
            //     try {
            //         // Mail::to($user_email)->send(new OtpMail($otp));
            //         // sendEmail($user_email, __('OTP Verification'), 'emails.otp', [
            //         //     'otp' => $otp,
            //         // ]);

            //         sendViewEmail(
            //             $user_email,
            //             __('OTP Verification'),
            //             'emails.otp',
            //             [
            //                 'otp' => $otp,
            //             ]
            //         );
            //     } catch (\Exception $e) {
            //         Log::error('Failed to send OTP email: ' . $e->getMessage());
            //     }
            // } else {
            //     $this->twilio->sendMessage(
            //         $request->phone,
            //         $otp
            //     );
            // }

            return response()->json([
                'data' => [
                    'message' => 'Success',
                    'user_type' => $newUser->userType->type,
                    'new_user_type' => $userType,
                    'user' => $newUser,
                ]
            ]);
        } elseif ($userType == 2) {  // Company
            $trn_image = $request->hasFile('trn_image')
                ? $this->fileService->upload($request->file('trn_image'))
                : null;

            $company_logo = $request->hasFile('company_logo')
                ? $this->fileService->upload($request->file('company_logo'))
                : null;

            // ✅ Update the user (no need to assign result to $cretedUser)
            $user->update([
                'acting_as' => 'customer',
                'iban' => $request->IBAN,
                'trn' => $request->TRN,
                'trn_image' => $trn_image['id'] ?? null,
                'company_logo' => $company_logo['id'] ?? null,
                'cr' => $request->CR,
                'user_types_id' => $userType,
            ]);

            Providesrappoverequest::create([
                'customer_id' => $user->id
            ]);

            // ✅ Get fresh copy of the updated user
            $newUser = $user->fresh();

            // ✅ Safely resolve image URLs
            $trnFile = $newUser->trn_image
                ? DB::table('files')->find($newUser->trn_image)
                : null;

            $companyLogoFile = $newUser->company_logo
                ? DB::table('files')->find($newUser->company_logo)
                : null;

            $trnImageUrl = $trnFile
                ? env('APP_URL') . 'storage/' . $trnFile->name
                : null;

            $companyLogoUrl = $companyLogoFile
                ? env('APP_URL') . 'storage/' . $companyLogoFile->name
                : null;

            $newUser->trn_image = $trnImageUrl;
            $newUser->company_logo = $companyLogoUrl;

            return response()->json([
                'data' => [
                    'message' => 'Success',
                    'user_type' => $newUser->userType->type,
                    'user' => $newUser->name ? $newUser : null,
                ]
            ]);
        }

        return response()->json(['message' => 'Invalid user type.'], 400);
    }

    public function switchUserRole(Request $request)
    {
        $user = Auth::user();
        $newRole = $request->input('role');  // 'customer' or 'provider'

        if (!in_array($newRole, ['customer', 'provider'])) {
            return response()->json(['message' => 'Invalid role.'], 422);
        }

        $user->acting_as = $newRole;
        $user->save();

        return response()->json([
            'message' => 'Switched successfully.',
            'acting_as' => $user->acting_as,
            'meta' => [
                'token' => $user->createToken($user->phone_number ?? 'auth_token')->plainTextToken
            ]
        ]);
    }

    public function RequestedDataForCreateCheckout()
    {
        // Fetch the authenticated user
        $user = Auth::user();

        // Return user profile as JSON response
        return response()->json([
            'message' => 'success',
            'data' => [
                'name' => $user->name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'street' => $user->street,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country,
                'postcode' => $user->postcode,
            ],
        ]);
    }

    public function profile()
    {
        // Fetch the authenticated user
        $user = Auth::user();
        // $user = auth()->id();

        // Mock loyalty points and travel trips for demonstration
        // $loyaltyPoints = 360;   // Example static value

        $activity_numbers = DB::table('bookings')->where('user_id', $user->id)->count();

        // Profile image - safely handle missing file records
        $profileImage = null;
        if ($user->live_photo) {
            $file = DB::table('files')->find($user->live_photo);
            if ($file && $file->name) {
                $profileImage = env('APP_URL') . 'storage/' . $file->name;
            }
        }

        // // Company Logo
        // if ($user->company_logo) {
        //     // $path = DB::table('files')->find($user->company_logo)->path;
        //     $company_logo_image_name = DB::table('files')->find($user->company_logo)->name;
        //     // Retrieve the user's profile image if available
        //     $company_logo = null;
        //     // Assuming the image is stored in 'storage'
        //     $company_logo = env('APP_URL') . 'storage/' . $image_name ? env('APP_URL') . 'storage/' . $image_name : null;
        // } else {
        //     $company_logo = null;
        // }

        // // National Id Image
        // if ($user->national_id_image) {
        //     // $path = DB::table('files')->find($user->national_id_image)->path;
        //     $national_id_image_name = DB::table('files')->find($user->national_id_image)->name;
        //     // Retrieve the user's profile image if available
        //     $national_id_image = null;
        //     // Assuming the image is stored in 'storage'
        //     $national_id_image = env('APP_URL') . 'storage/' . $national_id_image_name ? env('APP_URL') . 'storage/' . $national_id_image_name : null;
        // } else {
        //     $national_id_image = null;
        // }

        // // TRN Image
        // if ($user->trn_image) {
        //     // $path = DB::table('files')->find($user->live_photo)->path;
        //     $trn_image_name = DB::table('files')->find($user->trn_image)->name;
        //     // Retrieve the user's profile image if available
        //     $trn = null;
        //     // Assuming the image is stored in 'storage'
        //     $trn = env('APP_URL') . 'storage/' . $trn_image_name ? env('APP_URL') . 'storage/' . $trn_image_name : null;
        // } else {
        //     $trn = null;
        // }

        // Return user profile as JSON response
        return response()->json([
            'status' => 'success',
            'data' => [
                // 'id' => $user->id,
                // 'name' => $user->name,
                // 'phone' => $user->phone_number,
                // 'email' => $user->email,
                'code' => $user->code,
                'user_type' => $user->name ? $user->userType->type : null,
                'user_details' => $user->name ? $user : null,
                // 'loyalty_points' => $loyaltyPoints,
                'travel_trips' => $activity_numbers,
                'profile_image' => $profileImage,
                'room_id' => Room::where('user_id', $user->id)->where('user_type', $user->acting_as)->first()->room_id ?? null,
                // 'company_logo' => $company_logo,
                // 'trn_image' => $trn,
                // 'national_id_image' => $national_id_image,
            ],
        ]);
    }

    public function generateOTPforUpdateProfile(Request $request)
    {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);
        $user = auth()->user();

        // dd($phone_number);
        // Custom validation for Saudi phone numbers
        if ($request->email) {
            $email = User::where('email', $request->email)->value('email');
            if ($request->email == $email) {
                return response()->json([
                    'message' => 'The phone number is already exist',
                    'message_ar' => 'الإيميل موجود بالفعل',
                    'email' => $email,
                    // 'otp' => $otp,
                ], 200);
            } else {
                if (!$email) {
                    // return response()->json([
                    //     'message' => 'Phone number not found'
                    // ], 404);

                    // Delete old OTPs for this user
                    OTP::where('user_id', $user->id)->delete();

                    // Save the OTP in the database
                    OTP::create([
                        'user_id' => $user->id,
                        'email' => $request->email,
                        'otp' => $otp,
                        'created_at' => now(),
                    ]);

                    try {
                        // Mail::to($request->email)->send(new OtpMail($otp));
                        sendViewEmail($request->email, __('OTP Verification'), 'emails.otp', [
                            'otp' => $otp,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send OTP email: ' . $e->getMessage());
                    }

                    return response()->json([
                        'message' => 'OTP Sent Successfully',
                        'message_ar' => 'تم إرسال رمز التحقق بنجاح',
                        'email' => $request->email,
                        'otp' => $otp,
                    ], 200);
                }
            }
        }

        if ($request->phone_number) {
            $phone_number = User::where('phone_number', $request->phone_number)->value('phone_number');
            // dd('phone_number -> '.$phone_number.' $request->phone_number -> '. $request->phone_number);

            if ($request->phone_number == $phone_number) {
                return response()->json([
                    'message' => 'The phone number is already exist',
                    'message_ar' => 'رقم الهاتف موجود بالفعل',
                    'phone_number' => $phone_number,
                    // 'otp' => $otp,
                ], 200);
            } else {
                if (!$phone_number) {
                    // return response()->json([
                    //     'message' => 'Phone number not found'
                    // ], 404);

                    // Delete old OTPs for this user
                    OTP::where('user_id', $user->id)->delete();

                    // Save the OTP in the database
                    OTP::create([
                        'user_id' => $user->id,
                        'phone_number' => $request->phone_number,
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
                        'phone_number' => $request->phone_number,
                        'otp' => $otp,
                    ], 200);
                }
            }
        }
    }

    public function updateProfile(Request $request)
    {
        $user_id = Auth::user()->id;
        $request->validate([
            'name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'country_code' => 'nullable',
            'gender' => 'nullable',
            // 'phone_number' => 'nullable|numeric|unique:users,phone_number,' . $user_id,
            // 'email' => 'nullable',
            'phone' => [
                'nullable',
                'string',
                Rule::unique('users', 'phone_number')->ignore(auth()->id()),  // ignore current user
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore(auth()->id()),  // ignore current user
            ],
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
        if ($request->filled('email')) {
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
            'email' => $request->filled('email') ? $request->email : $user->email,
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

    public function deleteAccount()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $hasTrips = Activity::where('user_id', $user->id)->exists();
        $hasBookings = Booking::where('user_id', $user->id)->exists();
        $hasWishlist = Wishlist::where('user_id', $user->id)->exists();

        if ($hasTrips || $hasBookings || $hasWishlist) {
            return response()->json([
                'message' => 'You cannot delete your account because you have trips, bookings, or wishlist items.'
            ], 400);
        }

        $deleted = $user->delete();

        if ($deleted) {
            return response()->json([
                'status' => 'success',
                'message' => 'Account deleted successfully'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong'
        ], 500);
    }

    public function deactivateAccount()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }

        $deactivated = $user->update([
            'deactivate_request' => 'yes',
            'is_deactive' => 1,
        ]);

        if ($deactivated) {
            return response()->json([
                'status' => 'success',
                'message' => 'Account deactivated successfully'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Something went wrong'
        ], 500);
    }

    // for useing separate table for fcm_token
    // public function saveFcmToken(Request $request)
    // {
    //     $request->validate([
    //         'fcm_token' => 'required|string',
    //     ]);

    //     $user = auth()->user();

    //     $user->fcmTokens()->updateOrCreate(
    //         ['fcm_token' => $request->fcm_token],
    //         ['fcm_token' => $request->fcm_token]
    //     );

    //     return response()->json(['message' => 'Token saved successfully.']);
    // }

    // update the fcm_token column in users table
    public function saveFcmToken(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Update the fcm_token for the authenticated user
        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        // Return a success message
        return response()->json([
            'message' => 'Token saved successfully.',
            'message_ar' => 'تم حفظ الرمز المميز بنجاح.',
        ], 200);
    }

    public function add_phoneNumber(Request $request)
    {
        try {
            $phone = $request->input('phone_number');
            // Call the custom validation method
            if (!$this->validateSaudiPhoneNumber($phone)) {
                return response()->json([
                    'message' => 'The phone number is not valid.',
                    'message_ar' => 'رقم الهاتف غير صالح.',
                ], 422);
            }
            // dd(Auth::id());
            $user = User::where('id', Auth::id())->first();
            $updated = $user->update([
                'phone_number' => $request->phone_number,
            ]);
            return response()->json([
                'message' => 'Updated successfully',
                'message_ar' => 'تم التحديث بنجاح',
                'data' => $user,
            ], 200);
        } catch (ValidationException $e) {
            // Capture validation errors and return them in the response
            return response()->json([
                'message' => 'Validation failed.',
                'message_ar' => 'فشل التحقق من الصحة.',
                'errors' => $e->errors(),  // This gives an array of all validation errors
            ], 422);  // Unprocessable Entity
        }
    }

    public function PayViaWallet(Request $request)
    {
        $request->validate([
            'book_id' => 'nullable|exists:bookings,id',
            'cart_id' => 'nullable|exists:carts,id',
        ]);
        $booking_data = Booking::find($request->book_id);

        if ($request->book_id) {
            $total_price = Booking::where('id', $request->book_id)->first()->total_price;
        } else {
            $total_price = Cart::where('id', $request->cart_id)->first()->total_price;
        }

        // Payment
        $user = Auth::user();
        $wallet = $user->wallet;
        $walletBalance = $wallet->balance;
        $amountForPay = $total_price;

        $walletService = new WalletService();

        // Case 1: Wallet fully covers it
        if ($walletBalance >= $amountForPay) {
            $walletService->debit($user->id, $request->book_id, $request->cart_id, $amountForPay, null, 'Booking fully paid from wallet');

            if (!empty($request->cart_id)) {
                $cart_data = Cart::with('cartItems')->find($request->cart_id);
                // dd($cart_data);
                $tool_ids = $cart_data->cartItems->pluck('tool_id')->toArray();
                $tools = CommercialTool::whereIn('id', $tool_ids)->get();
                $provider_ids = $tools->pluck('user_id')->unique()->toArray();
                // dd($provider_ids);
                $toolNamesEn = $tools->pluck('name_en')->join(', ');
                $toolNamesAr = $tools->pluck('name_ar')->join(', ');

                $order_data = Order::with('orderItems')->where('user_id', auth()->id())->latest()->first();

                $notificationData = [
                    'title_en' => 'Order Payment Completed',
                    'title_ar' => 'تم الدفع بنجاح',
                    'body_en' => "A tool $toolNamesEn has been bought.",
                    'body_ar' => "تم شراء الاداة $toolNamesAr",
                    'type' => 'payment_success',
                    'book_id' => null,
                    'order_id' => $order_data->id,
                    'action' => 'order',
                    'send_to_all' => true,
                ];

                $pushController = new NotificationController();
                $pushController->pushWithoutBookId2(new PushNotificationRequest($notificationData), $provider_ids);

                $user_email = User::where('phone_number', $request->phone)->value('email');

                if ($user_email) {
                    try {
                        // Mail::to($user_email)->send(new OtpMail($otp));
                        // sendEmail($user_email, __('OTP Verification'), 'emails.otp', [
                        //     'otp' => $otp,
                        // ]);

                        sendViewEmail(
                            $user_email,
                            __('OTP Verification'),
                            'emails.otp',
                            [
                                'otp' => $otp,
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::error('Failed to send OTP email: ' . $e->getMessage());
                    }
                }
            } else if (!empty($request->book_id)) {
                $booking_data = Booking::where('id', $request->book_id)->update(['status_id' => 5, 'is_paied' => 1]);

                $booking_data2 = Booking::where('id', $request->book_id)->first();
                $activity_data = Activity::where('id', $booking_data2->activity_id)->first();
                // dd($activity_data);
                $notificationData = [
                    'title_en' => 'Booking Payment Completed',
                    'title_ar' => 'تم الدفع بنجاح',
                    'body_en' => "A trip $activity_data->title_en has been paid .",
                    'body_ar' => "تم دفع الرحلة '{$activity_data->title_ar}' ",
                    'type' => 'payment_success',
                    'book_id' => $request->book_id,
                    // 'order_id' => null,
                    'action' => 'payment',
                    'send_to_all' => false,
                ];
                $pushController = new NotificationController();
                $pushController->push_welcome(new PushNotificationRequest($notificationData), $activity_data->user_id);
            }

            $user_email = User::where('phone_number', $request->phone)->value('email');

            if ($user_email) {
                try {
                    // Mail::to($user_email)->send(new OtpMail($otp));
                    // sendEmail($user_email, __('OTP Verification'), 'emails.otp', [
                    //     'otp' => $otp,
                    // ]);

                    // sendViewEmail($user_email, __('OTP Verification'), 'emails.otp', [
                    //         'otp' => $otp,
                    //     ]);

                    sendViewEmail(
                        $user_email,
                        __('OTP Verification'),
                        'emails.otp',
                        [
                            'otp' => $otp,
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to send OTP email: ' . $e->getMessage());
                }
            }

            // Proceed to confirm booking
            // Save payment_status = paid, payment_method = wallet
            return response()->json([
                'status' => 'success',
                'message' => 'paid_from_wallet',
            ], 200);
        }
        // Case 2: Wallet covers partially → pay rest via card
        elseif ($walletBalance > 0) {
            $partialAmount = $walletBalance;
            $remainingAmount = $amountForPay - $walletBalance;

            // Deduct wallet part now
            $walletService->debit($user->id, $request->book_id, $request->cart_id, $amountForPay, null, 'Partial booking from wallet');

            // Now initiate HyperPay payment for remainingAmount
            return response()->json([
                'status' => 'success',
                'message' => 'partial_wallet',
                'total_price' => $total_price,
                'wallet_used' => $partialAmount,
                'rest_amount_due' => $remainingAmount,  // المبلغ المستحق
                'next_step' => 'redirect_to_payment',
                'url' => env('APP_URL') . 'create-checkout',
            ], 201);
        }
        // Case 3: Wallet = 0 → require full payment
        else {
            return response()->json([
                'status' => 'success',
                'message' => 'wallet_empty',
                'rest_amount_due' => $amountForPay,  // المبلغ المستحق
                'next_step' => 'redirect_to_payment',
                'url' => env('APP_URL') . 'create-checkout',
            ], 201);  // redirect
        }
    }

    public function myWallet()
    {
        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        return response()->json([
            'message' => 'success',
            'wallet' => $wallet,
        ]);
    }
}
