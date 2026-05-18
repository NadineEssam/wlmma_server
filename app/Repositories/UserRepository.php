<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function createUser($phoneNumber,){
        return User::create([
            'phone_number'=> $phoneNumber,
        ]);
    }
}
