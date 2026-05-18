<?php

namespace App\Http\Controllers\User;

use App\Models\OTP;
use App\Models\User;
use App\Enums\AUserTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\File\FileUploadRequest;
use App\Http\Requests\GenerateOTPRequest;
use App\Http\Requests\RegisterCompanyRequest;
use App\Http\Requests\RegisterIndividualRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\VerifyOTPRequest;
use App\Services\File\FileService;
use App\Services\OTPService;
use App\Utils\SendOTPSMS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController_old extends Controller
{
    public function __construct(
        public OTPService $service,
        public FileService $fileService
    ) {}


    // public function generateOTP(GenerateOTPRequest $request)
    // {
    //     $status = $this->service->generateOTP($request->phone_number);

    //     return response()->json([
    //         'message' => $status ? 'Success' : 'Failed, Try Again',
    //         'otp' => $status ?  $status : null
    //     ], $status ? 200 : 400);
    // }


    public function generateOTP(Request $request)
    {
        // Log the raw phone number entered
        // \Log::info('Raw Phone Number Entered: ' . $request->phone_numberx);

        // Validate the incoming phone number (ensure correct format)
        $validated = $request->validate([
            'phone' => 'required|string', // Change validation to string for testing
        ]);

        // Log the phone number after validation to ensure it matches
        // \Log::info('Validated Phone Number: ' . $validated['phone_numberx']);

        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Save the OTP in the database
        $otpRecord = OTP::create([
            'phone_number' => $validated['phone'],
            'otp' => $otp,
            'created_at' => now(),
        ]);

        // Check if the OTP was successfully saved
        if ($otpRecord) {
            // You can implement an SMS service to send the OTP here

            // Log OTP generation
            // \Log::info('OTP Generated: ' . $otp . ' for Phone Numberx: ' . $validated['phone_numberx']);

            // Return the response
            return response()->json([
                'message' => 'OTP Sent Successfully',
                'phone_number' => $validated['phone'],
                'otp' => $otp,  // Avoid returning OTP for security reasons in production
            ], 200);
        }

        // If OTP creation fails, return a failure response
        return response()->json([
            'message' => 'Failed to generate OTP, Try Again',
            'phone_number' => $validated['phone'],
            'otp' => null
        ], 400);
    }

    public function verifyOTP(VerifyOTPRequest $request)
    {
        $user = $this->service->validateOTP($request->phone_number, $request->otp);
        if ($user) {

            return response()->json([
                'data' => [
                    'message' => 'Success',
                    'needs_to_register' => $user->name ? false : true,
                    'user_type' => $user->name ? $user->userType->type : null,
                    'data' => $user->name ? $user : null,
                ],
                'meta' => [
                    'token' => $user->createToken($request->phone_number)->plainTextToken
                ]
            ], 200);
        } else {
            return response()->json([
                'data' => [
                    'message' => 'False'
                ],
                'meta' => [
                    'token' => ''
                ]
            ], 401);
        }
    }

    public function registerUser(RegisterUserRequest $request)
    {
        $user = $request->user();
        $user->name = $request->name;
        $user->user_types_id = AUserTypeEnum::USER;
        $user->save();
        return response()->json([
            'data' => [
                'message' => 'Success',
                'user_type' => $user->name ? $user->userType->type : null,
                'data' => $user->name ? $user : null,
            ]
        ], 200);
    }
    // public function registerCompany(RegisterCompanyRequest $request)
    // {
    //     $user = $request->user();
    //     $user->name = $request->name;
    //     $user->user_types_id = AUserTypeEnum::COMPANY;
    //     $user->IBAN = $request->IBAN;
    //     $user->TRN = $request->TRN;
    //     $user->CR = $request->CR;
    //     $user->save();
    //     return response()->json([
    //         'data' => [
    //             'message' => 'Success',
    //             'user_type' => $user->name ? $user->userType->type : null,
    //             'data' => $user->name ? $user : null,
    //         ]
    //     ], 200);
    // }

    public function registerCompany(RegisterCompanyRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'user_types_id' => AUserTypeEnum::COMPANY,
            'IBAN' => $request->IBAN,
            'IBAN' => $request->IBAN,
            'TRN' => $request->TRN,
            'CR' => $request->CR,
        ]);
        return response()->json([
            'data' => [
                'message' => 'Success',
                'user_type' => $user->name ? $user->userType->type : null,
                'data' => $user->name ? $user : null,
            ]
        ], 200);
    }



    public function registerIndividual(RegisterIndividualRequest $request)
    {
        // Handle the live photo upload

        $live_photo = $this->fileService->upload($request->file('live_photo'));

        // Update the user's information
        $user = $request->user();
        $user->name = $request->name;
        $user->user_types_id = AUserTypeEnum::INDIVIDUAL_BUSINESS;
        $user->iban = $request->IBAN;
        $user->national_id = $request->national_id;
        $user->live_photo = $live_photo['id'];
        $user->save();

        return response()->json([
            'data' => [
                'message' => 'Success',
                'user_type' => $user->name ? $user->userType->type : null,
                'data' => $user->name ? $user : null,
            ]
        ], 200);
    }
}
