<?php

namespace App\Services\Notification;

use App\Models\Notification;
use DateTimeImmutable;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\ServerError;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationSaver
{



    public static function Save(Notification $notification,): Notification
    {
        $notification->save();
        // dd($notification);
        return $notification;
    }
}
