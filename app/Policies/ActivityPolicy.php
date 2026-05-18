<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function create(User $user)
    {
        return $user->isServiceProvider();
    }

    public function update(User $user, Activity $activity)
    {
        return $user->id === $activity->user_id && $user->isServiceProvider();
    }

    public function delete(User $user, Activity $activity)
    {
        return $user->id === $activity->user_id && $user->isServiceProvider();
    }
}
