<?php

namespace App\Services;

use App\Http\Controllers\NotificationController;
use App\Http\Requests\PushNotificationRequest;
use App\Models\Admin;
use App\Models\Chat;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Database;
use Kreait\Firebase\Factory;

class FirebaseService
{
    protected $database;

    public function __construct()
    {
        $this->database = (new Factory)
            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')))
            ->withDatabaseUri('https://noti-dabd7-default-rtdb.firebaseio.com/')
            ->createDatabase();
    }

    // Send a message to a specific room
    // public function sendMessage($roomId, $message)
    // {
    //     $messagesRef = $this->database->getReference("chat_rooms/{$roomId}/messages");
    //     $messagesRef->push([
    //         'message' => $message,
    //         'timestamp' => now()->toDateString(),
    //     ]);
    // }

    // public function getUserRooms($userId)
    // {
    //     // Get all rooms from Firebase
    //     $roomsData = $this
    //         ->database
    //         ->getReference('chat_rooms')
    //         ->getValue();

    //     $userRooms = [];

    //     if ($roomsData) {
    //         foreach ($roomsData as $roomId => $room) {
    //             if (!isset($room['messages'])) {
    //                 continue;  // Skip empty rooms
    //             }

    //             $isUserInRoom = false;
    //             $latestMessage = null;
    //             $latestMessageTime = null;
    //             $latestMessageId = null;

    //             foreach ($room['messages'] as $messageId => $message) {
    //                 if (
    //                     (isset($message['sender_id']) && $message['sender_id'] == $userId) ||
    //                     (isset($message['receiver_id']) && $message['receiver_id'] == $userId)
    //                 ) {
    //                     $isUserInRoom = true;

    //                     // Track latest message
    //                     if (!isset($latestMessageTime) || $message['timestamp'] > $latestMessageTime) {
    //                         $latestMessage = $message;
    //                         $latestMessageTime = $message['timestamp'];
    //                         $latestMessageId = $messageId;  // Firebase message ID
    //                     }
    //                 }
    //             }

    //             if ($isUserInRoom) {
    //                 $room['latest_message'] = $latestMessage;
    //                 $room['latest_message_time'] = $latestMessageTime;
    //                 $room['latest_message_id'] = $latestMessageId;

    //                 // Optional: Get extra info from SQL if needed
    //                 $chatRecord = Chat::where('room_id', $roomId)
    //                     ->where('message_id', $latestMessageId)
    //                     ->first();
    //                 if ($chatRecord) {
    //                     $room['db_record'] = $chatRecord;  // link to SQL record
    //                 }

    //                 $userRooms[$roomId] = $room;
    //             }
    //         }
    //     }

    //     // Sort by latest message time (newest first)
    //     uasort($userRooms, function ($a, $b) {
    //         return strtotime($b['latest_message_time']) <=> strtotime($a['latest_message_time']);
    //     });

    //     return response()->json(['rooms' => array_values($userRooms)]);
    // }

   public function getUserRooms($userId)
{
    $roomsData = $this->database
        ->getReference('chat_rooms')
        ->getValue();

    $userRooms = [];

    if (!$roomsData) {
        return response()->json([
            'rooms' => []
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    foreach ($roomsData as $roomId => $room) {
        if (!isset($room['messages'])) {
            continue;
        }

        $isUserInRoom     = false;
        $latestMessage    = null;
        $latestMessageTime = null;
        $otherUserId      = null;

        foreach ($room['messages'] as $message) {
            $senderId   = $message['sender_id']   ?? null;
            $receiverId = $message['receiver_id'] ?? null;

            if ($senderId != $userId && $receiverId != $userId) {
                continue;
            }

            $isUserInRoom = true;

            // تحديد الطرف الآخر
            $otherUserId = ($senderId == $userId)
                ? $receiverId
                : $senderId;

            // تحديد أحدث رسالة
            $timestampInt = strtotime($message['timestamp'] ?? '');

            if (
                $timestampInt &&
                (is_null($latestMessageTime) || $timestampInt > $latestMessageTime)
            ) {
                $latestMessage     = $message;
                $latestMessageTime = $timestampInt;
            }
        }

        if (!$isUserInRoom || !$latestMessage) {
            continue;
        }

        $otherUser    = User::find($otherUserId);
        $profileImage = null;
        $userTypeName = 'admin';
        $otherName    = $this->getAdminName($otherUserId);

        if ($otherUser) {
            $userTypeName = is_object($otherUser->userType)
                ? $otherUser->userType->type
                : ($otherUser->userType ?? 'user');

            $otherName = ucwords(trim($otherUser->name . ' ' . $otherUser->last_name));

            // fallback بين company_logo و live_photo
            if ($userTypeName === 'company') {
                $fileId = $otherUser->company_logo ?: $otherUser->live_photo;
            } else {
                $fileId = $otherUser->live_photo ?: $otherUser->company_logo;
            }

            if ($fileId) {
                $file = DB::table('files')->find($fileId);

                if ($file) {
                    $profileImage = rtrim(env('APP_URL'), '/') . '/storage/' . $file->name;
                }
            }
        }

        $userRooms[] = [
            'room_id' => $roomId,
            'latest_message' => [
                'message'   => $latestMessage['message'] ?? '',
                'time'      => date('h:i A / Y-m-d', $latestMessageTime),
                'timestamp' => $latestMessageTime,
            ],
            'user' => [
                'id'        => $otherUser->id ?? $otherUserId,
                'name'      => $otherName,
                'user_type' => $userTypeName,
                'image'     => $profileImage,
            ],
        ];
    }

    usort($userRooms, function ($a, $b) {
        return $b['latest_message']['timestamp'] <=> $a['latest_message']['timestamp'];
    });

    return response()->json([
        'rooms' => $userRooms
    ], 200, [], JSON_UNESCAPED_UNICODE);
}

    public function getAllRooms()
    {
        $rooms = $this->database->getReference('chat_rooms')->getValue();
        return response()->json($rooms);
    }

    // public function sendMessage($roomId, $message, $receiverId)
    // {
    //     // try {
    //     // Reference to the chat room messages in Firebase
    //     $messagesRef = $this->database->getReference("chat_rooms/{$roomId}/messages");
    //     // dd($messagesRef->getvalue());

    //     // Push the new message to Firebase along with sender_id and receiver_id
    //     $messagesRef->push([
    //         'message' => $message,
    //         'sender_id' => auth()->id(),  // Store sender ID
    //         'sender_name' => auth()->user()->name,  // Store sender Name
    //         'sender_type' => auth()->user()->userType->type,  // Store sender Name
    //         'receiver_id' => $receiverId,  // Store receiver ID
    //         'receiver_name' => Admin::find($receiverId)->first_name . ' ' . Admin::find($receiverId)->second_name,  // Store sender ID
    //         'receiver_type' => 'admin',  // Store receiver Name
    //         'timestamp' => now()->toDateString(),
    //     ]);

    //     $messageId = $newMessageRef->getKey();

    //     Chat::create([
    //         'room_id' => $roomId,  // Store Room ID
    //         'message_id' => $messageId,  // store the Firebase message ID
    //         'sender_id' => auth()->id(),  // Store sender ID
    //         'sender_type' => auth()->user()->userType->type,  // Store sender Name
    //         'receiver_id' => $receiverId,  // Store receiver ID
    //         'receiver_type' => 'admin',  // Store receiver Name
    //     ]);
    //     // send notification to admin
    //     $notificationData = [
    //         'title_en' => 'New Message from ' . auth()->user()->name,
    //         'title_ar' => auth()->user()->name . ' رسالة جديدة من ',
    //         'body_en' => "{$message}",
    //         'body_ar' => "{$message}",
    //         'type' => 'chat_message',
    //         'action' => 'new_message',
    //         'send_to_all' => false,
    //     ];
    //     // dd($notificationData);
    //     // Call the push_book method from the notification controller
    //     $pushController = new NotificationController();  // Create an instance of the notification controller
    //     $pushController->push_messageto_admin(new PushNotificationRequest($notificationData), $receiverId);
    //     // } catch (\Exception $e) {
    //     //     // Log the error or handle it as needed
    //     //     Log::error("Failed to send message in room {$roomId}: " . $e->getMessage());
    //     // }
    // }
    public function sendMessage($roomId, $message, $receiverId)
    {
        // Reference to the chat room messages in Firebase
        $messagesRef = $this->database->getReference("chat_rooms/{$roomId}/messages");

        // Push the new message to Firebase and get the reference
        $newMessageRef = $messagesRef->push([
            'message' => $message,
            'user_type_sender' => auth()->user()->acting_as,
            'sender_id' => auth()->id(),
            'sender_name' => auth()->user()->name,
            'sender_type' => auth()->user()->userType->type,
            'receiver_id' => $receiverId,
            'receiver_name' => Admin::find($receiverId)->first_name . ' ' . Admin::find($receiverId)->second_name,
            'receiver_type' => 'admin',
            'timestamp' => Carbon::now('Asia/Riyadh')->toDateTimeString(),
        ]);

        // Get the auto-generated Firebase message ID
        $messageId = $newMessageRef->getKey();

        // Store in SQL as well
        Chat::create([
            'room_id' => $roomId,
            'message_id' => $messageId,  // store the Firebase message ID
            'sender_id' => auth()->id(),
            'user_type_sender' => auth()->user()->acting_as,
            'sender_type' => auth()->user()->userType->type,
            'receiver_id' => $receiverId,
            'receiver_type' => 'admin',
        ]);

        // Send notification to admin
        $notificationData = [
            'title_en' => 'New Message from ' . auth()->user()->name,
            'title_ar' => auth()->user()->name . ' رسالة جديدة من ',
            'body_en' => "{$message}",
            'body_ar' => "{$message}",
            'type' => 'chat_message',
            'action' => 'new_message',
            'send_to_all' => false,
        ];

        $pushController = new NotificationController();
        $pushController->push_messageto_admin(new PushNotificationRequest($notificationData), $receiverId);
    }

    public function sendMessage_admin($roomId, $message, $receiverId)
    {
        // try {
        // Reference to the chat room messages in Firebase
        $messagesRef = $this->database->getReference("chat_rooms/{$roomId}/messages");
        // dd($messagesRef->getvalue());
        $receiver_data = User::find($receiverId);
        $receiver_type = $receiver_data->userType->type;
        // dd(auth()->user()->first_name . ' ' . auth()->user()->second_name);
        // Push the new message to Firebase along with sender_id and receiver_id
        $newMessageRef = $messagesRef->push([
            'message' => $message,
            'sender_id' => auth()->id(),  // Store sender ID
            'sender_name' => auth()->user()->first_name . ' ' . auth()->user()->second_name,  // Store sender name
            'sender_type' => 'admin',  // Store sender type
            'receiver_id' => $receiverId,  // Store receiver ID
            'receiver_name' => $receiver_data->name,  // Store receiver ID
            'receiver_type' => $receiver_type,  // Store receiver type
            'timestamp' => Carbon::now('Asia/Riyadh')->toDateTimeString(),
        ]);

        // Get the auto-generated Firebase message ID
        $messageId = $newMessageRef->getKey();

        // Store in SQL as well
        Chat::create([
            'room_id' => $roomId,
            'message_id' => $messageId,  // store the Firebase message ID
            'sender_id' => auth()->id(),
            'sender_type' => 'admin',
            'receiver_id' => $receiverId,
            'receiver_type' => $receiver_type,
        ]);

        // send notification to admin
        $notificationData = [
            'title_en' => 'New Message from ' . auth()->user()->first_name . ' ' . auth()->user()->second_name,
            'title_ar' => auth()->user()->first_name . ' ' . auth()->user()->second_name . ' رسالة جديدة من ',
            'body_en' => "{$message}",
            'body_ar' => "{$message}",
            'type' => 'chat_message',
            'action' => 'new_message',
            'send_to_all' => false,
        ];
        // dd($notificationData);
        // Call the push_book method from the notification controller
        $pushController = new NotificationController();  // Create an instance of the notification controller
        $pushController->push_messageto_users(new PushNotificationRequest($notificationData), $receiverId);
        // } catch (\Exception $e) {
        //     // Log the error or handle it as needed
        //     Log::error("Failed to send message in room {$roomId}: " . $e->getMessage());
        // }
    }

    public function getUserName($userId)
    {
        // Assuming you have a User model and a users table
        $user = User::find($userId);
        return $user ? $user->name : 'Unknown User';  // Default to 'Unknown User' if not found
    }

    public function getAdminName($userId)
    {
        // Assuming you have a User model and a users table
        $user = Admin::find($userId);
        return $user ? $user->first_name . ' ' . $user->second_name : 'Unknown User';  // Default to 'Unknown User' if not found
    }

    public function getLatestMessage($roomId)
    {
        try {
            $messagesRef = $this->database->getReference("chat_rooms/{$roomId}/messages");
            $messages = $messagesRef->getValue();
            // dd($messages);

            // Assuming you want the latest message, order by timestamp (you can adjust this logic)
            $latestMessage = collect($messages)->sortByDesc('timestamp')->last();

            if ($latestMessage) {
                if ($latestMessage['sender_type'] == 'admin') {
                    $latestMessage['sender_name'] = $this->getAdminName($latestMessage['sender_id']);
                } else {
                    $latestMessage['sender_name'] = $this->getUserName($latestMessage['sender_id']);
                }

                if ($latestMessage['receiver_type'] == 'admin') {
                    $latestMessage['receiver_name'] = $this->getAdminName($latestMessage['receiver_id']);
                } else {
                    $latestMessage['receiver_name'] = $this->getUserName($latestMessage['receiver_id']);
                }
            }

            return $latestMessage;
        } catch (\Exception $e) {
            Log::error("Failed to fetch the latest message for room {$roomId}: " . $e->getMessage());
            return ['error' => 'An error occurred while fetching the message.'];
        }
    }

    public function getMessages($roomId)
    {
        // Get messages from Firebase
        $messagesRef = $this->database->getReference("chat_rooms/{$roomId}/messages");
        $messagesSnapshot = $messagesRef->getValue();

        $messages = [];
        if ($messagesSnapshot) {
            foreach ($messagesSnapshot as $key => $message) {
                // Retrieve the sender and receiver names using the sender_id and receiver_id
                if ($message['sender_type'] == 'admin') {
                    $sender = Admin::find($message['sender_id']);
                    $sender_name = $sender->first_name . ' ' . $sender->second_name;
                    $sender_name = $sender->first_name . ' ' . $sender->second_name;
                    // dd('if sender');
                } else {
                    $sender = User::find($message['sender_id']);
                    $sender_name = $sender->name;
                    // dd('else sender');
                }

                // echo ($message['sender_name']);

                if ($message['receiver_type'] == 'admin') {
                    $receiver = Admin::find($message['receiver_id']);
                    $receiver_name = $receiver->first_name . ' ' . $sender->second_name;
                    // dd('if receiver');
                } else {
                    $receiver = User::find($message['receiver_id']);
                    $receiver_name = $receiver->name;
                    // dd('else receiver');
                }
                // echo ($message['receiver_name']);

                // $sender = User::find($message['sender_id']);
                // $receiver = User::find($message['receiver_id']);

                $messages[] = [
                    'message' => $message['message'] ?? null,
                    'sender_id' => $message['sender_id'],
                    'sender_name' => $sender_name,
                    'sender_type' => $message['sender_type'],
                    'receiver_id' => $message['receiver_id'],
                    'receiver_name' => $receiver_name,
                    'receiver_type' => $message['receiver_type'],
                    'timestamp' => $message['timestamp'] ?? null,
                ];
            }
        }

        return $messages;
    }

    // Close a specific chat room
    public function closeChat($roomId)
    {
        try {
            $roomRef = $this->database->getReference("chat_rooms/{$roomId}");
            $roomRef->update([
                'status' => 'closed',
                'closed_at' => now()->timestamp,
            ]);
        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error("Failed to close chat room {$roomId}: " . $e->getMessage());
        }
    }
}
