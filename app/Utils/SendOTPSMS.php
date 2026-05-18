<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;

class SendOTPSMS
{
    public static function SendOtpMessage($phoneNumber, $otp) {
        $response = Http::post(env('SMS_BASE_URL'),[
            'userName'=> env('SMS_USER_NAME'),
            'apiKey'=> env('SMS_API_KEY'),
            'numbers'=> $phoneNumber,
            'userSender'=> env('SENDER_NAME'),
            'msg'=> "Your OTP code for Walmaa is $otp"
        ]);

        return $response->json();
    }
}
