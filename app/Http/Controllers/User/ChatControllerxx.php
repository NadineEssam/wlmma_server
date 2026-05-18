<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\Admin;
use App\Models\Message;
use App\Chat\FirebaseChat;
use Illuminate\Http\Request;
use App\Chat\ChatApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Controllers\NotificationController;

class ChatControllerxx extends Controller
{


    // create and send message to admin
    public function createChat(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:admins,id', // Validates user id exists in 'users' table
            'message' => 'required',
        ]);

        $user_id = auth()->id();
        $sender_name = auth()->user()->name;
        $receiver_name = Admin::find($request->receiver_id)->first_name . ' ' . Admin::find($request->receiver_id)->second_name;
        $chat = new Message([
            'sender_id' => $user_id,
            'sender_type' => 'user',
            'sender_name' => $sender_name,
            'receiver_id' => $request->receiver_id,
            'receiver_name' => $receiver_name,
            'receiver_type' => 'admin',
            'message' => $request->message
        ]);
        $chat->save();

        // send notification to admin
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
        $pushController->push_messageto_admin(new PushNotificationRequest($notificationData), $request->receiver_id);

        return response()->json([
            'message' => 'Send Successfully.',
            'data' => $chat,
        ], 201); // HTTP Status 201 for successful creation
    }

    // show  all chats between user and specific admin
    public function showChatList(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id', // Validates user id exists in 'users' table
        ]);

        $user_id = auth()->id(); // Get the authenticated user's ID
        $sender_name = auth()->user()->name; // Get the authenticated user's name
        $user_type_id = auth()->user()->user_types_id; // Get the user's type ID
        $user_data =  Auth::user();
        $user_data_type = User::find($user_id)->userType;
        // Determine the sender type based on the user's type ID
        $sender_type = match ($user_type_id) {
            1 => "user",
            default => null,
        };

        // Validate the sender type
        if (!$sender_type) {
            return response()->json([
                'message' => 'User type is not supported for chat functionality.',
            ], 400); // HTTP 400: Bad Request
        }

        // Validate the admin_id from the request
        $admin_id = $request->admin_id;
        // if (!$admin_id || !Admin::find($admin_id)) {
        //     return response()->json([
        //         'message' => 'Invalid admin ID provided.',
        //     ], 400); // HTTP 400: Bad Request
        // }

        // Fetch messages only between the authenticated user and the specific admin
        $allMessages = Message::where(function ($query) use ($user_id, $sender_type, $admin_id) {
            $query->where('sender_id', $user_id)
                ->where('sender_type', $sender_type)
                ->where('receiver_id', $admin_id)
                ->where('receiver_type', 'admin');
        })->orWhere(function ($query) use ($user_id, $admin_id) {
            $query->where('receiver_id', $user_id)
                ->where('sender_id', $admin_id)
                ->where('sender_type', 'admin');
        })
            ->orderby('id', 'desc')
            ->get();

        // Fetch the admin's details
        $admin = Admin::find($admin_id, ['id', 'first_name', 'second_name']);

        // Prepare the response message
        $receiver_name = "{$admin->first_name} {$admin->second_name}";

        return response()->json([
            'message' => "All chats for sender '{$sender_name}' (type: {$sender_type}) with admin: {$receiver_name}.",
            // 'customer' => $user_data->userType->type,
            // 'admin' => $admin,
            'data' => $allMessages,
        ], 200); // HTTP 200: Success
    }

    // show last chat MSG between user and specific admin
    public function lastChatMsg(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id', // Validates user id exists in 'users' table
        ]);

        $user_id = auth()->id(); // Get the authenticated user's ID
        $sender_name = auth()->user()->name; // Get the authenticated user's name
        $user_type_id = auth()->user()->user_types_id; // Get the user's type ID
        $user_data =  Auth::user();
        $user_data_type = User::find($user_id)->userType;
        // Determine the sender type based on the user's type ID
        $sender_type = match ($user_type_id) {
            1 => "user",
            default => null,
        };

        // Validate the sender type
        if (!$sender_type) {
            return response()->json([
                'message' => 'User type is not supported for chat functionality.',
            ], 400); // HTTP 400: Bad Request
        }

        // Validate the admin_id from the request
        $admin_id = $request->admin_id;
        // if (!$admin_id || !Admin::find($admin_id)) {
        //     return response()->json([
        //         'message' => 'Invalid admin ID provided.',
        //     ], 400); // HTTP 400: Bad Request
        // }

        // Fetch messages only between the authenticated user and the specific admin
        $allMessages = Message::where(function ($query) use ($user_id, $sender_type, $admin_id) {
            $query->where('sender_id', $user_id)
                ->where('sender_type', $sender_type)
                ->where('receiver_id', $admin_id)
                ->where('receiver_type', 'admin');
        })->orWhere(function ($query) use ($user_id, $admin_id) {
            $query->where('receiver_id', $user_id)
                ->where('sender_id', $admin_id)
                ->where('sender_type', 'admin');
        })
            ->orderby('id', 'desc')
            ->first();

        // Fetch the admin's details
        $admin = Admin::find($admin_id, ['id', 'first_name', 'second_name']);

        // Prepare the response message
        $receiver_name = "{$admin->first_name} {$admin->second_name}";

        return response()->json([
            'message' => "All chats for sender '{$sender_name}' (type: {$sender_type}) with admin: {$receiver_name}.",
            // 'customer' => $user_data->userType->type,
            // 'admin' => $admin,
            'data' => $allMessages,
        ], 200); // HTTP 200: Success
    }
}
