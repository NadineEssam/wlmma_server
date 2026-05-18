<?php

namespace App\Services\Notification;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Models\UserFcmToken;
use App\Services\Notification\NotificationPusher;
use App\Services\Notification\NotificationSaver;
use Illuminate\Database\Eloquent\Collection;

class NotificationBuilder
{
    private int $user_id;
    private array $user_ids = [];
    private ?string $user_acting_as = null;
    private string $title_en;
    private string $title_ar;
    private string $body_en;
    private string $body_ar;
    private ?int $book_id = null;
    private ?int $order_id = null;
    private ?string $fcm_token;
    private ?array $fcm_tokens = [];
    // private ?string $fcm_token = "eYTtJ4aiTbDWxQcqVp-eXy:APA91bHHgdBfvd8_McVYw6MxkU19pwF8NaHhhBUHu8_U-HaJj7n-NYEHcbeiTqUYCF6FJjPJ9W8y1ll-IoxJtkGrd3UuEvRU1Ic8wi-lM3w5FtUZNRvkZeM";  // Initialize with null
    // private ?array $fcm_tokens = null;  // Initialize with null
    private string $type = 'general';
    private ?string $action = null;
    private Notification $notification;

    public function adminId(int $userId): NotificationBuilder
    {
        $userExists = Admin::where('id', $userId)->exists();
        if (!$userExists) {
            throw new \InvalidArgumentException('Invalid user ID');
        }
        $this->user_id = $userId;
        return $this;
    }

    public function user_acting_as(string $userActingAs): NotificationBuilder
    {
        $userExists = User::where('acting_as', $userActingAs)->exists();
        if (!$userExists) {
            throw new \InvalidArgumentException('Invalid user acting');
        }
        $this->user_acting_as = $userActingAs;
        return $this;
    }

    public function userId(int $userId): NotificationBuilder
    {
        $user = User::select('acting_as')->find($userId);

        if (!$user) {
            throw new \InvalidArgumentException('Invalid user ID');
        }

        $this->user_id = $userId;
        $this->user_acting_as = $user->acting_as;

        return $this;
    }

    public function book_id(?int $book_id): NotificationBuilder
    {
        // $bookExists = Booking::where('id', $book_id)->exists();
        // if (!$bookExists) {
        //     throw new \InvalidArgumentException('Invalid book ID');
        // }
        // dd($this->book_id);
        $this->book_id = $book_id;
        return $this;
    }

    public function order_id(?int $order_id): NotificationBuilder
    {
        // $orderExists = Order::where('id', $order_id)->exists();
        // if (!$orderExists) {
        //     throw new \InvalidArgumentException('Invalid order ID');
        // }
        // dd($this->order_id);
        $this->order_id = $order_id;
        return $this;
    }

    public function userIds(array $userIds): NotificationBuilder
    {
        $this->user_ids = $userIds;
        return $this;
    }

    public function fcmToken(?string $fcm_token): NotificationBuilder
    {
        $this->fcm_token = $fcm_token;
        return $this;
    }

    public function fcmTokens(?array $fcm_tokens): NotificationBuilder
    {
        $this->fcm_tokens = $fcm_tokens;
        return $this;
    }

    public function title_en(string $title): NotificationBuilder
    {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title_en must not be empty');
        }
        $this->title_en = $title;
        return $this;
    }

    public function title_ar(string $title): NotificationBuilder
    {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title_ar must not be empty');
        }
        $this->title_ar = $title;
        return $this;
    }

    public function body_en(string $body): NotificationBuilder
    {
        if (empty($body)) {
            throw new \InvalidArgumentException('Body_en must not be empty');
        }
        $this->body_en = $body;
        return $this;
    }

    public function body_ar(string $body): NotificationBuilder
    {
        if (empty($body)) {
            throw new \InvalidArgumentException('Body_ar must not be empty');
        }
        $this->body_ar = $body;
        return $this;
    }

    public function type(string $type): NotificationBuilder
    {
        // Validate type against allowed values
        if (!in_array($type, Notification::NOTIFICATIONS_ALLOWED_TYPES)) {
            throw new \InvalidArgumentException('Invalid notification type');
        }
        $this->type = $type;
        return $this;
    }

    public function action(string $action): NotificationBuilder
    {
        $this->action = $action;
        return $this;
    }

    // public function sendToOneOLD(): string
    // {
    //     $notification = new Notification([
    //         'user_id' => $this->user_id,
    //         'user_acting_as' => $this->user_acting_as,
    //         'title_en' => $this->title_en,
    //         'title_ar' => $this->title_ar,
    //         'body_en' => $this->body_en,
    //         'body_ar' => $this->body_ar,
    //         'type' => $this->type,
    //         'action' => $this->action,
    //     ]);

    //     $saved_notification = NotificationSaver::Save($notification);
    //     // skip push notification if fcm_token == null
    //     if ($this->fcm_token != null) {
    //         return NotificationPusher::SendToOne($saved_notification, $this->fcm_token);
    //     } else {
    //         return "Saved but not pushed (fcm_token is null)";
    //     }
    // }
    public function sendToOne(): string
    {
        // Create a new notification instance
        $notification = new Notification([
            'user_id' => $this->user_id,
            'user_acting_as' => $this->user_acting_as,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'body_en' => $this->body_en,
            'body_ar' => $this->body_ar,
            'type' => $this->type,
            'action' => $this->action,
        ]);

        // Save the notification to the database
        $saved_notification = NotificationSaver::Save($notification);

        // Retrieve the user's FCM token from the user_fcm_tokens table
        $user = User::find($this->user_id);

        $userFcmToken = User::where('id', $this->user_id)->where('acting_as', $user?->acting_as)->first();
        // dd($userFcmToken->fcm_token);
        // Skip push notification if FCM token is null
        if ($userFcmToken && $userFcmToken->fcm_token) {
            // Send the notification to the user's device using the retrieved FCM token
            return NotificationPusher::SendToOne($saved_notification, $userFcmToken->fcm_token);
        } else {
            return 'Saved but not pushed (fcm_token is null)';
        }
    }

    public function sendToOne_messageto_users(): string
    {
        // Create a new notification instance
        $notification = new Notification([
            'user_id' => $this->user_id,
            'user_acting_as' => $this->user_acting_as,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'body_en' => $this->body_en,
            'body_ar' => $this->body_ar,
            'type' => $this->type,
            'action' => $this->action,
        ]);

        // Save the notification to the database
        $saved_notification = NotificationSaver::Save($notification);

        // Retrieve the user's FCM token from the user_fcm_tokens table
        $user = User::find($this->user_id);

        $userFcmToken = User::where('id', $this->user_id)->where('acting_as', $user?->acting_as)->first();
        // dd($userFcmToken->fcm_token);
        // Skip push notification if FCM token is null
        if ($userFcmToken && $userFcmToken->fcm_token) {
            // Send the notification to the user's device using the retrieved FCM token
            return NotificationPusher::SendToOne($saved_notification, $userFcmToken->fcm_token);
        } else {
            return 'Saved but not pushed (fcm_token is null)';
        }
    }

    public function sendToOne_messageto_admin(): string
    {
        // Create a new notification instance
        $notification = new Notification([
            'user_id' => $this->user_id,
            'user_acting_as' => $this->user_acting_as,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'body_en' => $this->body_en,
            'body_ar' => $this->body_ar,
            'type' => $this->type,
            'action' => $this->action,
        ]);

        // dd($notification);
        // Save the notification to the database
        $saved_notification = NotificationSaver::Save($notification);
        // dd($saved_notification);

        // Retrieve the user's FCM token from the user_fcm_tokens table
        $userFcmToken = Admin::where('id', $this->user_id)->first();
        // dd($userFcmToken->fcm_token);
        // Skip push notification if FCM token is null
        if ($userFcmToken && $userFcmToken->fcm_token) {
            // Send the notification to the user's device using the retrieved FCM token
            return NotificationPusher::SendToOne($saved_notification, $userFcmToken->fcm_token);
        } else {
            return 'Saved but not pushed (fcm_token is null)';
        }
    }

    public function sendToOne_book(): string
    {
        // Create a new notification instance
        $notification = new Notification([
            'user_id' => $this->user_id,
            'user_acting_as' => $this->user_acting_as,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'body_en' => $this->body_en,
            'body_ar' => $this->body_ar,
            'type' => $this->type,
            'book_id' => $this->book_id,
            'order_id' => $this->order_id,
            'action' => $this->action,
        ]);
        // Save the notification to the database
        $saved_notification = NotificationSaver::Save($notification);

        // Retrieve the user's FCM token from the user_fcm_tokens table
        $user = User::find($this->user_id);

        $userFcmToken = User::where('id', $this->user_id)->where('acting_as', $user?->acting_as)->first();
        // dd($saved_notification);
        // Skip push notification if FCM token is null
        if ($userFcmToken && $userFcmToken->fcm_token) {
            // Send the notification to the user's device using the retrieved FCM token
            // dd(NotificationPusher::SendToOne($saved_notification, $userFcmToken->fcm_token));
            return NotificationPusher::SendToOne($saved_notification, $userFcmToken->fcm_token);
        } else {
            return 'Saved but not pushed (fcm_token is null)';
        }
    }

    // public function sendToMany(): string
    // {
    //     $notification = new Notification([
    //         'title_en' => $this->title_en,
    //         'title_ar' => $this->title_ar,
    //         'body_en' => $this->body_en,
    //         'body_ar' => $this->body_ar,
    //         'type' => $this->type,
    //         'user_acting_as' => $this->user_acting_as,
    //         'action' => $this->action,
    //     ]);

    //     // Iterate over each user ID and create a new notification for each user
    //     foreach ($this->user_ids as $userId) {
    //         $notification = new Notification([
    //             'user_id' => $userId,
    //             'user_acting_as' => $this->user_acting_as,
    //             'title_en' => $notification->title_en,
    //             'title_ar' => $notification->title_ar,
    //             'body_en' => $notification->body_en,
    //             'body_ar' => $notification->body_ar,
    //             'type' => $notification->type,
    //             'action' => $notification->action,
    //         ]);

    //         NotificationSaver::Save($notification);
    //     }

    //     $tokensToSend = array_filter($this->fcm_tokens, fn($token) => $token !== null && $token !== '');

    //     // Push notifications only for tokens with non-null FCM tokens
    //     if (!empty($tokensToSend)) {
    //         return NotificationPusher::SendToMany($notification, $tokensToSend);
    //     } else {
    //         return 'Saved but not pushed (no valid FCM tokens)';
    //     }
    // }

    public function sendToMany(array $userIds): string
    {
        $notification = new Notification([
            'user_acting_as' => $this->user_acting_as,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'body_en' => $this->body_en,
            'body_ar' => $this->body_ar,
            'type' => $this->type,
            'book_id' => $this->book_id,
            'order_id' => $this->order_id,
            'action' => $this->action,
        ]);

        // dd($userIds);
        // dd($notification);
        if (!empty($userIds)) {
            $allUsers = $userIds;
            // dd($this->order_id);
            // Iterate over each user ID and create a new notification for each user
            foreach ($allUsers as $userId) {
                $notification = new Notification([
                    'user_id' => $userId,
                    'user_acting_as' => $this->user_acting_as,
                    'title_en' => $notification->title_en,
                    'title_ar' => $notification->title_ar,
                    'body_en' => $notification->body_en,
                    'body_ar' => $notification->body_ar,
                    'type' => $notification->type,
                    'book_id' => $notification->book_id,
                    'order_id' => $notification->order_id,
                    'action' => $notification->action,
                ]);

                NotificationSaver::Save($notification);
            }
            // dd($notification);
            $tokensToSend = User::whereIn('id', $allUsers)
                ->pluck('fcm_token')
                ->filter()
                ->values()
                ->toArray();
            // dd($tokensToSend);
        } else {
            $allUsers = $this->getAllUsers();
            foreach ($allUsers->pluck('id')->toArray() as $userId) {
                $notification = new Notification([
                    'user_id' => $userId,
                    'user_acting_as' => $this->user_acting_as,
                    'title_en' => $notification->title_en,
                    'title_ar' => $notification->title_ar,
                    'body_en' => $notification->body_en,
                    'body_ar' => $notification->body_ar,
                    'type' => $notification->type,
                    'book_id' => $request->book_id ?? null,
                    'order_id' => $request->order_id ?? null,
                    'action' => $notification->action,
                ]);

                NotificationSaver::Save($notification);
            }
            // dd($notification);
            $tokensToSend = $allUsers->pluck('fcm_token')->filter()->values()->toArray();
        }
        // dd($tokensToSend);
        // $tokensToSend = array_filter($this->fcm_tokens, fn($token) => $token !== null && $token !== '');
        // Push notifications only for tokens with non-null FCM tokens
        if (!empty($tokensToSend)) {
            // dd(NotificationPusher::SendToMany($notification, $tokensToSend));
            return NotificationPusher::SendToMany($notification, $tokensToSend);
        } else {
            return 'Savssssssed but not pushed (no valid FCM tokens)';
        }
    }

    public function sendToMany_book(array $userIds): string
    {
        $notification = new Notification([
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'body_en' => $this->body_en,
            'body_ar' => $this->body_ar,
            'type' => $this->type,
            'action' => $this->action,
        ]);

        // Iterate over each user ID and create a new notification for each user
        foreach ($userIds as $userId) {
            $notification = new Notification([
                'user_id' => $userId,
                'title_en' => $notification->title_en,
                'title_ar' => $notification->title_ar,
                'body_en' => $notification->body_en,
                'body_ar' => $notification->body_ar,
                'type' => $notification->type,
                'action' => $notification->action,
            ]);

            NotificationSaver::Save($notification);
        }

        // $tokensToSend = array_filter($this->fcm_tokens, fn($token) => $token !== null && $token !== '');

        // Retrieve the user's FCM token from the user_fcm_tokens table
        $userFcmToken = User::whereIn('id', $userIds)->pluck('fcm_token')->toArray();
        // $all_booking_users = WaitingList::where('activity_id', $activity->id)->pluck('user_id')->toArray();

        // dd($userFcmToken);
        // Skip push notification if FCM token is null
        // Push notifications only for tokens with non-null FCM tokens
        if ($userFcmToken) {
            // Send the notification to the user's device using the retrieved FCM token

            return NotificationPusher::SendToMany($notification, $userFcmToken);
        } else {
            return 'Saved but not pushed (fcm_token is null)';
        }

        // if (!empty($tokensToSend)) {
        //     return NotificationPusher::SendToMany($notification, $tokensToSend);
        // } else {
        //     return "Saved but not pushed (no valid FCM tokens)";
        // }
    }

    // public function sendToAll(): string
    // {
    //     $this->setNotification(new Notification([
    //         'title_en' => $this->title_en,
    //         'title_ar' => $this->title_ar,
    //         'body_en' => $this->body_en,
    //         'body_ar' => $this->body_ar,
    //         'type' => $this->type,
    //         'action' => $this->action,
    //     ]));

    //     $allUsers = $this->getAllUsers();
    //     // dd($allUsers);
    //     // Iterate over each user ID and create a new notification for each user
    //     foreach ($allUsers->pluck('id')->toArray() as $userId) {
    //         $notificationToSave = new Notification([
    //             'user_id' => $userId,
    //             'title_en' => $this->getNotification()->title_en,
    //             'title_ar' => $this->getNotification()->title_ar,
    //             'body_en' => $this->getNotification()->body_en,
    //             'body_ar' => $this->getNotification()->body_ar,
    //             'type' => $this->getNotification()->type,
    //             'action' => $this->getNotification()->action,
    //         ]);

    //         NotificationSaver::Save($notificationToSave);
    //     }

    //     $tokensToSend = $allUsers->pluck('fcm_token')->filter()->values()->toArray();

    //     $chunks = array_chunk($tokensToSend, 490);
    //     $pushResults = [];

    //     // dd($tokensToSend);
    //     // Push notifications only for tokens with non-null FCM tokens
    //     if (!empty($tokensToSend)) {
    //         foreach ($chunks as $chunk) {
    //             $pushResults[] = NotificationPusher::SendToMany($this->getNotification(), $chunk);
    //         }
    //     } else {
    //         return 'Saved but not pushed (no valid FCM tokens)';
    //     }
    // }

    public function sendToAll(): string
    {
        // Create a base notification object
        $notification = new Notification([
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'body_en' => $this->body_en,
            'body_ar' => $this->body_ar,
            'type' => $this->type,
            'action' => $this->action,
        ]);

        $this->setNotification($notification);

        $allUsers = $this->getAllUsers();

        // Save notification for each user
        foreach ($allUsers->pluck('id') as $userId) {
            $notificationToSave = clone $notification;
            $notificationToSave->user_id = $userId;
            NotificationSaver::Save($notificationToSave);
        }

        // Get valid, unique tokens (flatten if stored as arrays)
        $tokensToSend = collect($allUsers)
            ->pluck('fcm_token')
            ->filter(fn($t) => !empty($t) && is_string($t) && strlen($t) > 50)
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
        // dd($tokensToSend);

        if (empty($tokensToSend)) {
            return 'Saved but not pushed (no valid FCM tokens)';
        }

        // Push in chunks & log FCM responses
        $chunks = array_chunk($tokensToSend, 490);
        $pushResults = [];

        foreach ($chunks as $chunk) {
            $response = NotificationPusher::SendToMany($notification, $chunk);
            $pushResults[] = $response;

            // Log response for debugging
            \Log::info('FCM Push Response', [
                'tokens' => $chunk,
                'response' => $response
            ]);
        }

        // Debug output: User IDs + Tokens
        $tokensWithUsers = $allUsers
            ->filter(fn($user) => !empty($user->fcm_token))
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'fcm_token' => substr($user->fcm_token, 0, 20) . '...'  // shorten for readability
                ];
            })
            ->unique('fcm_token')
            ->values()
            ->toArray();

        return 'Notifications saved and pushed to '
            . count($tokensWithUsers)
            . ' devices. Push results: '
            . json_encode($pushResults);
    }

    private function getAllUsers(): Collection
    {
        // return User::all('id', 'fcm_token');
        // dd(User::where('user_types_id', 1)->get(['id', 'fcm_token']));
        // return User::where('user_types_id', 1)->get(['id', 'fcm_token']);
        return User::where('acting_as', 'customer')->get(['id', 'fcm_token']);
    }

    /** Get the value of notification */

    /**
     * Get the value of notification
     */
    private function initializeNotification(): void
    {
        if (!isset($this->notification)) {
            $this->setNotification(new Notification([
                'user_acting_as' => $this->user_acting_as,
                'title_en' => $this->title_en,
                'title_ar' => $this->title_ar,
                'body_en' => $this->body_en,
                'body_ar' => $this->body_ar,
                'type' => $this->type,
                'book_id' => $this->book_id,
                'order_id' => $this->order_id,
                'action' => $this->action,
            ]));
        }
    }

    public function getNotification(): Notification
    {
        if (!isset($this->notification)) {
            throw new \RuntimeException('Notification property is not initialized.');
        }

        return $this->notification;
    }

    /**
     * Set the value of notification
     */
    public function setNotification(Notification $notification): self
    {
        $this->notification = $notification;

        return $this;
    }
}
