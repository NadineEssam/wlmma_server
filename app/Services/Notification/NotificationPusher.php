<?php

namespace App\Services\Notification;

use App\Models\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\Messaging\ServerError;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;

class NotificationPusher
{
    // private static Messaging $messaging;

    // public static function init(Factory $factory): void
    // {
    //     self::$messaging = $factory->createMessaging();
    // }

    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public static function SendToOne(Notification $notification, string $fcm_token): string
    {
        $cloudMessage = CloudMessage::withTarget('token', $fcm_token);
        $message = self::createCloudMessage($cloudMessage, $notification);
        // dd($notification);
        return self::sendMessage($message);
    }

    // public static function SendToMany(Notification $notification, array $fcm_tokens): string
    // {
    //     $cloudMessage = CloudMessage::new();
    //     $message = self::createCloudMessage($cloudMessage, $notification);
    //     return self::sendMessage($message, true, $fcm_tokens);
    // }

    public static function SendToMany(Notification $notification, array $fcm_tokens): string
    {
        // Remove empty/null tokens and duplicates
        $fcm_tokens = collect($fcm_tokens)
            ->filter(fn($token) => !empty($token) && is_string($token))
            ->unique()
            ->values()
            ->toArray();

        foreach ($fcm_tokens as $token) {
            $cloudMessage = CloudMessage::withTarget('token', $token);
            $message = self::createCloudMessage($cloudMessage, $notification);
            self::sendMessage($message);
        }

        return 'Messages sent to ' . count($fcm_tokens) . ' unique devices.';
    }

    // private static function createCloudMessage(CloudMessage $cloudMessage, Notification $notification): CloudMessage
    // {
    //     return $cloudMessage->withNotification(
    //         [
    //             'title' => $notification->title_en,
    //             'body' => $notification->body_en,
    //         ]
    //     )->withData(
    //         [
    //             'title_en' => $notification->title_en,
    //             'title_ar' => $notification->title_ar,
    //             'body_en' => $notification->body_en,
    //             'body_ar' => $notification->body_ar,
    //             'type' => $notification->type,
    //             'action' => $notification->action,
    //         ]
    //     );
    // }

    // private static function createCloudMessage(CloudMessage $cloudMessage, Notification $notification): CloudMessage
    // {
    //     return $cloudMessage
    //         ->withNotification([
    //             'title' => $notification->title_en,
    //             'body' => $notification->body_en,
    //         ])
    //         ->withData([
    //             'title_en' => $notification->title_en,
    //             'title_ar' => $notification->title_ar,
    //             'body_en' => $notification->body_en,
    //             'body_ar' => $notification->body_ar,
    //             'type' => $notification->type,
    //             'action' => $notification->action,
    //         ])  // );
    //         ->withApnsConfig([
    //             'headers' => [
    //                 'apns-priority' => '10',  // High priority (instant delivery)
    //             ],
    //             'payload' => [
    //                 'aps' => [
    //                     'alert' => [
    //                         'title' => $notification->title_en,
    //                         'body' => $notification->body_en,
    //                     ],
    //                     'sound' => 'default',  // play notification sound
    //                 ],
    //             ],
    //         ]);
    // }
    
    
    private static function createCloudMessage(CloudMessage $cloudMessage, Notification $notification): CloudMessage
    {
        return $cloudMessage
            ->withNotification([
                'title' => $notification->title_en,
                'body' => $notification->body_en,
            ])
            ->withData([
                'title_en' => $notification->title_en,
                'title_ar' => $notification->title_ar,
                'body_en' => $notification->body_en,
                'body_ar' => $notification->body_ar,
                'type' => $notification->type,
                'action' => $notification->action,
            ])  // );
            ->withApnsConfig([
                'headers' => [
                    'apns-priority' => '10',  // High priority (instant delivery)
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $notification->title_en,
                            'body' => $notification->body_en,
                        ],
                        'badge' => 1,  // Show badge on app icon
                        'sound' => 'default',  // play notification sound
                    ],
                ],
            ]);
    }
    

    private static function sendMessage(CloudMessage $message, bool $isMultiCast = false, array $fcm_tokens = []): string
    {
        try {
            $messaging = app('firebase.messaging');
            if ($isMultiCast) {
                $messaging->sendMulticast($message, $fcm_tokens);
            } else {
                // dd($message);
                $messaging->send($message);
            }
            $response_message = 'Success';
        } catch (NotFound $e) {
            $response_message = 'Notification failed: Target device not found.';
        } catch (InvalidMessage $e) {
            $response_message = 'Notification failed: Invalid message format.';
        } catch (ServerError $e) {
            $response_message = 'Notification failed: FCM server error.';
        } catch (MessagingException|FirebaseException $e) {
            $response_message = 'Notification failed: ' . $e->getMessage();
        }
        return $response_message;
    }
}
