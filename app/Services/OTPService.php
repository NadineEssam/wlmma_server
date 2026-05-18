<?php

namespace App\Services;

use App\Models\OTP;
use App\Models\User;
use App\Repositories\OTPRepository;
use App\Repositories\UserRepository;
use App\Utils\SendOTPSMS;

class OTPService
{
    public function __construct(
        public OTPRepository $repo,
        public UserRepository $userRepository
    ) {
    }
    public function generateOTP($phoneNumber, $attempts = 0)
    {
        if ($attempts == 10) {
            return false;
        }
        $otp = random_int(100000, 999999);

        if ($this->repo->usable($otp, $phoneNumber)) {
            $this->repo->createOTP($otp, $phoneNumber);

            //TODO: uncomment where the credentials are available
            // $res = SendOTPSMS::SendOtpMessage($phoneNumber, $otp);
            // if (!empty($res->code)) {
            //     return false;
            // }
        } else {
            $this->generateOTP($phoneNumber,  $attempts + 1);
        }

        return $otp;
    }

    public function validateOTP($phoneNumber, $otp)
    {
        $otp = OTP::where([
            ['phone_number', $phoneNumber],
            ['otp', $otp],
            ['created_at' , '>', now()->subMinutes(5)]
        ])->first();

        if (!$otp) {
            return null;
        }
        OTP::where(
            'phone_number',
            $phoneNumber
        )->delete();

        $user = User::where('phone_number', $phoneNumber)->first();
        if (!$user) {
            $user = $this->userRepository->createUser($phoneNumber);
        }

        return $user;
    }
}
