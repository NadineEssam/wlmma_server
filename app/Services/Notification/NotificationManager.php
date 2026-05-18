<?php

namespace App\Services\Notification;

use App\Services\Notification\NotificationBuilder;


class NotificationManager
{

    public static function Builder(): NotificationBuilder
    {
        return new NotificationBuilder();
    }
}
