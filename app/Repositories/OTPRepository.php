<?php

namespace App\Repositories;

use App\Models\OTP;

class OTPRepository
{
    public function usable($otp, $phoneNumber):bool{
        return !OTP::query()->where('phone_number', $phoneNumber)->where('otp', $otp)->exists();
    }

    public function createOTP($otp, $phoneNumber) {
        OTP::create([
            'otp' => $otp,
            'phone_number' => $phoneNumber,
        ]);
    }
}
