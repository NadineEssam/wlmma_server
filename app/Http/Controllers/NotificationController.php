<?php

namespace App\Http\Controllers;

use App\Http\Requests\PushNotificationRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notification\NotificationBuilder;
use App\Services\Notification\NotificationManager;
use App\Services\Notification\NotificationPusher;
use App\Services\Notification\NotificationSaver;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function getList(Request $request)
    {
        $userId = auth()->id();
        $pageSize = $request->input('per_page', 20);
        $pageNumber = $request->input('page', 1);

        $notifications = Notification::where('user_id', $userId)
            ->where('user_acting_as', auth()->user()->acting_as)
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNumber);
        // Log::info('Notifications ==> ', ['notifications' => $notifications]);
        // $unseenCount = Notification::where('user_id', $userId)
        //     ->where('is_seen', false)
        //     ->count();

        $unseenCount = Notification::where('user_id', $userId)
            ->where('user_acting_as', auth()->user()->acting_as)
            ->where('is_seen', false)
            ->count();

        return response()->json([
            'current_page' => $notifications->currentPage(),
            'data' => $notifications->items(),
            'last_page' => $notifications->lastPage(),  // ✅ last page number
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'unseen_count' => $unseenCount
        ], 200);
        // return $notifications;
    }

    public function unseenCount(Request $request): \Illuminate\Http\JsonResponse
    {
        $userId = auth()->user()->id;

        // Efficiently retrieve unseen notification count
        $unseenCount = Notification::where('user_id', $userId)
            ->where('user_acting_as', auth()->user()->acting_as)
            ->where('is_seen', false)
            ->count();

        $data = [
            'count' => $unseenCount,
        ];

        return response()->json($data);
    }

    /*
     * Mark notification by id as seen
     */
    public function seen(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the incoming request data
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
        ]);

        $userId = auth()->user()->id;

        // Update the specified notification to mark it as seen
        $notificationId = $request->input('notification_id');
        Notification::where('id', $notificationId)->where('user_id', $userId)->where('user_acting_as', auth()->user()->acting_as)->update(['is_seen' => true]);
        // $unseenCount = Notification::where('user_id', $userId)
        //     ->where('user_acting_as', auth()->user()->acting_as)
        //     ->where('is_seen', false)
        //     ->count();

        $unseenCount = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->where('user_acting_as', auth()->user()->acting_as)
            ->update(['is_seen' => true]);

        // Return a success response
        return response()->json([
            'message' => 'Notification marked as seen successfully.',
            'message_ar' => 'تم تميز الاشعار بأنه مقروء.',
            'unseen_count' => $unseenCount,
        ], 200);
    }

    /*
     * Mark all notifications as seen per user
     */
    public function seenAll(): \Illuminate\Http\JsonResponse
    {
        $userId = auth()->user()->id;
        if (auth()->check()) {
            // Update all notifications associated with the authenticated user to mark them as seen
            Notification::where('user_id', $userId)->where('user_acting_as', auth()->user()->acting_as)->update(['is_seen' => true]);

            // Return a success response
            return response()->json([
                'message' => 'Notifications marked as seen successfully.',
                'message_ar' => 'تم تميز الاشعار بأنه مقروء.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Unautherized',
                'message_ar' => 'غير مصرح به'
            ], 401);
        }
    }

    /*
     * Push notification
     */
    public function pushOnly(Request $request): \Illuminate\Http\JsonResponse  // OLD
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);
        $notification = self::testNotification();
        $message = NotificationPusher::SendToOne($notification, $request->fcm_token);
        return response()->json(['message' => $message, 'notification' => $notification]);
    }

    // public function pushOnly(Request $request): \Illuminate\Http\JsonResponse
    // {
    //     $request->validate([
    //         'fcm_token' => 'required|string',
    //         'user_id' => 'required'
    //     ]);
    //     // $notification = self::testNotification($request->user_id);
    //     $notificationData = [
    //         'title_en' => $request->title_en,
    //         'title_ar' => $request->title_ar,
    //         'body_en' => $request->body_en,
    //         'body_ar' => $request->body_ar,
    //         'action' => isset($request->book_id) ? Notification::toReservation($request->book_id) : Notification::GENERAL_ACTION,
    //         'type' => $request->type,
    //         'book_id' => $request->book_id ?? null,
    //     ];

    //     $notification = self::testNotification($request->user_id, $notificationData);
    //     $message = NotificationPusher::SendToOne($notification, $request->fcm_token);
    //     return response()->json(['message' => $message, 'notification' => $notification]);
    // }

    public function saveAndPush(): \Illuminate\Http\JsonResponse
    {
        $notification = self::testNotification();
        $message = NotificationManager::Builder()
            ->userId(auth()->user()->id)
            ->user_acting_as(auth()->user()->acting_as)
            ->fcmToken(auth()->user()->fcm_token)
            ->title_en($notification->title_en)
            ->title_ar($notification->title_ar)
            ->body_en($notification->body_en)
            ->body_ar($notification->body_ar)
            ->type($notification->type)
            ->action($notification->action)
            ->sendToOne();

        return response()->json(['message' => $message, 'notification' => $notification]);
    }

    public function saveOnly(): \Illuminate\Http\JsonResponse
    {
        $notification = self::testNotification();
        NotificationSaver::Save($notification);
        return response()->json(
            ['message' => 'Success', 'notification' => $notification]
        );
    }

    // public function push(PushNotificationRequest $request): \Illuminate\Http\JsonResponse
    // {
    //     $fcmToken = $request->input('fcm_token');
    //     $sender =  NotificationManager::Builder()
    //         // ->userId($request->user_id)
    //         ->title_ar($request->title_ar)
    //         ->title_en($request->title_en)
    //         ->body_en($request->body_en)
    //         ->body_ar($request->body_ar)
    //         ->type($request->type)
    //         ->action(Notification::toReservation(0));
    //     if ($request->send_to_all) {
    //         $sender->sendToAll();
    //     } else {
    //         // dd($sender->userId($request->user_id)->sendToOne());
    //         $sender->userId($request->user_id)->sendToOne();
    //     }

    //     return response()->json(
    //         ['message' => 'Success', "notification" => $sender->getNotification()]
    //     );
    // }

    public function pushWithoutBookId(PushNotificationRequest $request, $user_id): \Illuminate\Http\JsonResponse
    {
        $sender = NotificationManager::Builder()
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            // ->action(Notification::toReservation(0));
            ->action(Notification::add_new($request->action));

        // Ensure the notification is initialized before sending
        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            // 'action' => Notification::toReservation(0),
            'action' => Notification::add_new($request->action),
        ]));

        $allUsers = User::where('user_types_id', 1)->get(['id', 'fcm_token']);
        $userIds = $allUsers->pluck('id')->filter()->values()->toArray();
        if ($request->send_to_all) {
            $sender->SendToMany($user_id);
        } else {
            // $sender->userId($request->user_id)->sendToOne();
            $sender->userId($user_id)->sendToOne_book();
        }

        return response()->json(
            ['message' => 'Success', 'notification' => $sender->getNotification()]
        );
    }

    /*
     * public function pushWithoutBookId2(PushNotificationRequest $request): \Illuminate\Http\JsonResponse
     *    {
     *        Log::info($request->all()) ;
     *        // Read user_id from request body (single int or array)
     *        $user_ids = $request->input('user_id');
     *        $user_ids = is_array($user_ids) ? $user_ids : [$user_ids];
     *        Log::info(["user_ids => ",$user_ids]);
     *
     *        $user_data = User::whereIn('id', $user_ids)->first();
     *        Log::info(["User => ",$user_data]);
     *
     *        $sender = NotificationManager::Builder()
     *            ->user_acting_as($user_data->acting_as)
     *            ->title_ar($request->title_ar)
     *            ->title_en($request->title_en)
     *            ->body_en($request->body_en)
     *            ->body_ar($request->body_ar)
     *            ->type($request->type)
     *            ->book_id($request->book_id)
     *            ->order_id($request->order_id)
     *            ->action(Notification::add_new($request->action));
     *
     *        $sender->setNotification(new Notification([
     *            'title_en' => $request->title_en,
     *            'title_ar' => $request->title_ar,
     *            'body_en' => $request->body_en,
     *            'body_ar' => $request->body_ar,
     *            'type' => $request->type,
     *            'book_id' => $request->book_id ?? null,
     *            'order_id' => $request->order_id ?? null,
     *            'action' => Notification::add_new($request->action),
     *        ]));
     *
     *        if ($request->send_to_all) {
     *            $sender->SendToMany($user_ids);
     *        } else {
     *            $sender->userId($user_ids[0])->sendToOne_book();
     *        }
     *
     *        return response()->json([
     *            'message' => 'Success',
     *            'notification' => $sender->getNotification(),
     *        ]);
     *    }
     */
    public function pushWithoutBookId2(PushNotificationRequest $request, array $user_id = []): \Illuminate\Http\JsonResponse
    {
        // لو اتبعت من كود داخلي هياخد من الـ parameter
        // لو اتبعت من Postman هياخد من الـ request body
        if (!empty($user_id)) {
            $user_ids = $user_id;
        } else {
            $user_ids = $request->input('user_id');
            $user_ids = is_array($user_ids) ? $user_ids : [$user_ids];
        }

        Log::info(['user_ids => ', $user_ids]);

        $user_data = User::whereIn('id', $user_ids)->first();
        Log::info(['User => ', $user_data]);

        if (!$user_data) {
            return response()->json(['message' => 'No users found'], 404);
        }

        $sender = NotificationManager::Builder()
            ->user_acting_as($user_data->acting_as)
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            ->book_id($request->book_id)
            ->order_id($request->order_id)
            ->action(Notification::add_new($request->action));

        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            'book_id' => $request->book_id ?? null,
            'order_id' => $request->order_id ?? null,
            'action' => Notification::add_new($request->action),
        ]));

        if ($request->send_to_all) {
            $sender->SendToMany($user_ids);
        } else {
            $sender->userId($user_ids[0])->sendToOne_book();
        }

        return response()->json([
            'message' => 'Success',
            'notification' => $sender->getNotification(),
        ]);
    }

    // Send Notification to user/s
    public function push(PushNotificationRequest $request): \Illuminate\Http\JsonResponse
    {
        $sender = NotificationManager::Builder()
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            // ->action(Notification::toReservation(0));
            ->action(Notification::add_new($request->action));

        // Ensure the notification is initialized before sending
        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            // 'action' => Notification::toReservation(0),
            'action' => Notification::add_new($request->action),
        ]));

        $allUsers = User::where('user_types_id', 1)->get(['id', 'fcm_token']);
        $userIds = $allUsers->pluck('id')->filter()->values()->toArray();
        if ($request->send_to_all) {
            $sender->SendToMany($userIds);
        } else {
            // $sender->userId($request->user_id)->sendToOne();
            $sender->userId($request->user_id)->sendToOne_book();
        }

        return response()->json(
            ['message' => 'Success', 'notification' => $sender->getNotification()]
        );
    }

    public function push_messageto_users(PushNotificationRequest $request, $user_id): \Illuminate\Http\JsonResponse
    {
        $sender = NotificationManager::Builder()
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            ->action('new_message');

        // Ensure the notification is initialized before sending
        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            'action' => 'new_message',
        ]));

        // if ($request->send_to_all) {
        //     $sender->sendToAll();
        // } else {
        // dd($user_id);
        $sender->userId($user_id)->sendToOne_messageto_users();
        // }

        return response()->json(
            ['message' => 'Success', 'notification' => $sender->getNotification()]
        );
    }

    public function push_messageto_admin(PushNotificationRequest $request, $user_id): \Illuminate\Http\JsonResponse
    {
        $sender = NotificationManager::Builder()
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            ->action('new_message');
        // dd($sender);

        // Ensure the notification is initialized before sending
        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            'action' => 'new_message',
        ]));

        // if ($request->send_to_all) {
        //     $sender->sendToAll();
        // } else {
        // dd($user_id);
        $sender->adminId($user_id)->sendToOne_messageto_admin();
        // }

        return response()->json(
            ['message' => 'Success', 'notification' => $sender->getNotification()]
        );
    }

    public function push_book(PushNotificationRequest $request, $user_id): \Illuminate\Http\JsonResponse
    {
        $sender = NotificationManager::Builder()
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            ->book_id($request->book_id)
            ->action('booking');

        // Ensure the notification is initialized before sending
        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            'book_id' => $request->book_id,
            'action' => 'booking',
        ]));

        if ($request->send_to_all) {
            $sender->sendToAll();
        } else {
            // dd($user_id);
            $sender->userId($user_id)->sendToOne_book();
        }

        return response()->json(
            ['message' => 'Success', 'notification' => $sender->getNotification()]
        );
    }

    public function push_welcome(PushNotificationRequest $request, $user_id): \Illuminate\Http\JsonResponse  // OLD
    {
        $user_data = User::where('id', $user_id)->first();
        $sender = NotificationManager::Builder()
            ->user_acting_as($user_data->acting_as)
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            ->book_id($request->book_id)
            ->action($request->action);

        // Ensure the notification is initialized before sending
        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            'book_id' => $request->book_id,
            'action' => $request->action,
        ]));

        if ($request->send_to_all) {
            $sender->sendToAll();
        } else {
            // dd($user_id);
            $sender->userId($user_id)->sendToOne_book();
        }

        return response()->json(
            ['message' => 'Success', 'notification' => $sender->getNotification()]
        );
    }

    // public function push_welcome(PushNotificationRequest $request): \Illuminate\Http\JsonResponse
    // {
    //     // لو send_to_all true → نبعت لكل المستخدمين
    //     if ($request->send_to_all) {
    //         // جلب كل المستخدمين (أو اللي عندهم fcm_token)
    //         $users = User::whereNotNull('fcm_token')->get();

    //         foreach ($users as $user) {
    //             // عمل notification ديناميكي لكل user
    //             $notificationData = [
    //                 'title_en' => $request->title_en,
    //                 'title_ar' => $request->title_ar,
    //                 'body_en' => $request->body_en,
    //                 'body_ar' => $request->body_ar,
    //                 'action' => isset($request->book_id) ? Notification::toReservation($request->book_id) : Notification::GENERAL_ACTION,
    //                 'type' => $request->type,
    //                 'book_id' => $request->book_id ?? null,
    //             ];

    //             $notification = self::testNotification($user->id, $notificationData);

    //             // إرسال مباشرة باستخدام FCM
    //             if ($user->fcm_token) {
    //                 NotificationPusher::SendToOne($notification, $user->fcm_token);
    //             }
    //         }
    //     } else {
    //         // ارسال لمستخدم محدد
    //         $user_id = $request->user_id;
    //         if (!$user_id) {
    //             return response()->json(['message' => 'user_id missing'], 400);
    //         }
    //         // Log::info('User ID ==> ' . $user_id);

    //         $user = User::find($user_id);
    //         // Log::info('User ==> ' . $user);
    //         if (!$user || !$user->fcm_token) {
    //             return response()->json(['message' => 'Invalid user or missing fcm_token'], 400);
    //         }

    //         $notificationData = [
    //             'title_en' => $request->title_en,
    //             'title_ar' => $request->title_ar,
    //             'body_en' => $request->body_en,
    //             'body_ar' => $request->body_ar,
    //             'action' => isset($request->book_id) ? Notification::toReservation($request->book_id) : Notification::GENERAL_ACTION,
    //             'type' => $request->type,
    //             'book_id' => $request->book_id ?? null,
    //         ];

    //         $notification = self::testNotification($user->id, $notificationData);
    //         NotificationPusher::SendToOne($notification, $user->fcm_token);
    //     }

    //     return response()->json(['message' => 'Success']);
    // }

    public function push_book_many(PushNotificationRequest $request, array $userIds): \Illuminate\Http\JsonResponse
    {
        $sender = NotificationManager::Builder()
            ->title_ar($request->title_ar)
            ->title_en($request->title_en)
            ->body_en($request->body_en)
            ->body_ar($request->body_ar)
            ->type($request->type)
            ->action('booking');

        // Ensure the notification is initialized before sending
        $sender->setNotification(new Notification([
            'title_en' => $request->title_en,
            'title_ar' => $request->title_ar,
            'body_en' => $request->body_en,
            'body_ar' => $request->body_ar,
            'type' => $request->type,
            'action' => 'booking',
        ]));

        // if ($request->send_to_all) {
        $sender->sendToMany_book($userIds);
        // } else {
        //     dd($user_id);
        //     $sender->userId($user_id)->sendToOne_book();
        // }

        return response()->json(
            ['message' => 'Success', 'notification' => $sender->getNotification()]
        );
    }

    public function testNotification(): Notification  // OLD
    {
        $user_id = 1;
        $notification = new Notification();
        $notification->title_en = 'Touring';
        $notification->title_ar = 'تورينج';
        $notification->body_en = 'Welcome to Touring';
        $notification->body_ar = 'مرحبًا بك في تورينج';
        $notification->action = Notification::toReservation(0);
        $notification->user_id = $user_id;
        $notification->type = Notification::GENERAL_TYPE;
        return $notification;
    }

    // public function testNotification($userID, $data = []): Notification
    // {
    //     $user_id = $userID;

    //     $notification = new Notification();

    //     // Titles ديناميكي
    //     $notification->title_en = $data['title_en'] ?? 'Default Title EN';
    //     $notification->title_ar = $data['title_ar'] ?? 'Default Title AR';

    //     // Body ديناميكي
    //     $notification->body_en = $data['body_en'] ?? 'Default Body EN';
    //     $notification->body_ar = $data['body_ar'] ?? 'Default Body AR';

    //     // Action ديناميكي (مثال: ممكن تبعت book_id لو موجود)
    //     if (isset($data['book_id']) && $data['book_id'] > 0) {
    //         $notification->action = Notification::toReservation($data['book_id']);
    //     } else {
    //         $notification->action = $data['action'] ?? Notification::GENERAL_ACTION;
    //     }

    //     // User & type
    //     $notification->user_id = $user_id;
    //     $notification->type = $data['type'] ?? Notification::GENERAL_TYPE;

    //     return $notification;
    // }
}
