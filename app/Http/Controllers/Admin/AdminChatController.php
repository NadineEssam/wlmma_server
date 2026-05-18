<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Admin;
use App\Models\Message;
use App\Chat\FirebaseChat;
use Illuminate\Http\Request;
use App\Chat\ChatApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Controllers\NotificationController;

class AdminChatController extends Controller
{


    // create and send message to admin
    public function createChat(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id', // Validates user id exists in 'users' table
            'message' => 'required',
        ]);

        $user_id = auth()->id();
        $sender_name = auth()->user()->first_name . ' ' . auth()->user()->second_name;
        // dd($sender_name);
        $receiver_type = User::find($request->receiver_id)->user_types_id;
        $receiver_name = User::find($request->receiver_id)->name;
        // 1 => user , 2 => company , 3 => individual-business
        if ($receiver_type == 1) {
            $type = "user";
        } else if ($receiver_type == 2) {
            $type = "company";
        } else if ($receiver_type == 3) {
            $type = "individual-business";
        }
        $chat = new Message([
            'sender_id' => $user_id,
            'sender_name' => $sender_name,
            'sender_type' => 'admin',
            'receiver_id' => $request->receiver_id,
            'receiver_name' => $receiver_name,
            'receiver_type' => $type,
            'message' => $request->message
        ]);
        $chat->save();

        $notificationData = [
            'title_en' => "New Message from {$sender_name}",
            'title_ar' => "رسالة جديدة من {$sender_name}",
            'body_en' => "{$request->message}",
            'body_ar' => "{$request->message}",
            'type' => 'message',
            'action' => 'update_activity',
            'send_to_all' => false,
        ];
        // dd($notificationData);
        // Call the push_book method from the notification controller
        $pushController = new NotificationController();  // Create an instance of the notification controller
        $pushController->push_messageto_users(new PushNotificationRequest($notificationData), $request->receiver_id);

        return response()->json([
            'message' => 'Send Successfully.',
            'message_ar' => 'أرسل بنجاح.',
            'data' => $chat,
        ], 201); // HTTP Status 201 for successful creation
    }

    // show  all chats between admin and specific user
    public function showChatList(Request $request)
    {
        // Validate the request inputs
        $request->validate([
            // 'user_type' => 'required|integer|in:1,2,3', // Validate user_type as integer and within allowed values
            'receiver_id' => 'required|exists:users,id' // Validate receiver_id exists in the 'users' table
        ]);

        $admin_id = auth()->id(); // Get the authenticated admin's ID

        // Fetch authenticated admin's data
        $admin_data = Admin::find($admin_id);
        if (!$admin_data) {
            return response()->json([
                'message' => 'Authenticated admin not found.',
                'message_ar' => 'لم يتم العثور على المشرف المعتمد.',
            ], 404); // HTTP 404: Not Found
        }

        $admin_name = $admin_data->first_name . " " . $admin_data->second_name;

        // Map user_type_id to the corresponding user type
        // $user_type = match ($request->user_type) {
        //     1 => "user",
        //     2 => "company",
        //     3 => "individual-business",
        //     default => null
        // };

        // if (!$user_type) {
        //     return response()->json([
        //         'message' => 'Invalid user type provided.'
        //     ], 400); // HTTP 400: Bad Request
        // }

        // Fetch messages between the admin and the specified receiver
        $receiver_id = $request->receiver_id;
        $types = User::find($receiver_id)->user_types_id;
        // dd($types);
        if ($types == 1) {
            $user_type = "user";
        } else if ($types == 2) {
            $user_type = "company";
        } else if ($types == 3) {
            $user_type = "individual-business";
        }


        $allMessages = Message::where(function ($query) use ($admin_id, $receiver_id, $user_type) {
            $query->where('sender_id', $admin_id)
                ->where('sender_type', 'admin')
                ->where('receiver_id', $receiver_id)
                ->where('receiver_type', $user_type);
        })->orWhere(function ($query) use ($admin_id, $receiver_id, $user_type) {
            $query->where('receiver_id', $admin_id)
                ->where('sender_id', $receiver_id)
                ->where('sender_type', $user_type);
        })
            ->orderby('id', 'desc')
            ->get();

        // Fetch receiver's details
        $receiver = User::find($receiver_id);
        $receiver_name = $receiver ? $receiver->name : 'Unknown User';

        return response()->json([
            'message' => "All chats for admin '{$admin_name}' with '{$receiver_name}' (type: '{$user_type}').",
            'message_ar' => "جميع الدردشات للمشرف '{$admin_name}' مع '{$receiver_name}' (النوع: '{$user_type}').",
            'data' => $allMessages,
        ], 200); // HTTP Status 200: Success
    }

    // show last chat MSG between admin and specific user
    public function lastChatMsg(Request $request)
    {
        // Validate the request inputs
        $request->validate([
            // 'user_type' => 'required|integer|in:1,2,3', // Validate user_type as integer and within allowed values
            'receiver_id' => 'required|exists:users,id' // Validate receiver_id exists in the 'users' table
        ]);

        $admin_id = auth()->id(); // Get the authenticated admin's ID

        // Fetch authenticated admin's data
        $admin_data = Admin::find($admin_id);
        if (!$admin_data) {
            return response()->json([
                'message' => 'Authenticated admin not found.',
                'message_ar' => 'لم يتم العثور على المشرف المعتمد.',
            ], 404); // HTTP 404: Not Found
        }

        $admin_name = $admin_data->first_name . " " . $admin_data->second_name;

        // Map user_type_id to the corresponding user type
        // $user_type = match ($request->user_type) {
        //     1 => "user",
        //     2 => "company",
        //     3 => "individual-business",
        //     default => null
        // };

        // if (!$user_type) {
        //     return response()->json([
        //         'message' => 'Invalid user type provided.'
        //     ], 400); // HTTP 400: Bad Request
        // }

        // Fetch messages between the admin and the specified receiver
        $receiver_id = $request->receiver_id;
        $types = User::find($receiver_id)->user_types_id;
        // dd($types);
        if ($types == 1) {
            $user_type = "user";
        } else if ($types == 2) {
            $user_type = "company";
        } else if ($types == 3) {
            $user_type = "individual-business";
        }


        $allMessages = Message::where(function ($query) use ($admin_id, $receiver_id, $user_type) {
            $query->where('sender_id', $admin_id)
                ->where('sender_type', 'admin')
                ->where('receiver_id', $receiver_id)
                ->where('receiver_type', $user_type);
        })->orWhere(function ($query) use ($admin_id, $receiver_id, $user_type) {
            $query->where('receiver_id', $admin_id)
                ->where('sender_id', $receiver_id)
                ->where('sender_type', $user_type);
        })
            ->orderby('id', 'desc')
            ->first();

        // Fetch receiver's details
        $receiver = User::find($receiver_id);
        $receiver_name = $receiver ? $receiver->name : 'Unknown User';

        return response()->json([
            'message' => "All chats for admin '{$admin_name}' with '{$receiver_name}' (type: '{$user_type}').",
            'message_ar' => "جميع الدردشات للمشرف '{$admin_name}' مع '{$receiver_name}' (النوع: '{$user_type}').",
            'data' => $allMessages,
        ], 200); // HTTP Status 200: Success
    }
}
