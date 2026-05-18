<?php

namespace App\Http\Controllers;

use App\Http\Requests\PushNotificationRequest;
use App\Models\Admin;
use App\Models\Room;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;

class ChatController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    // Start a new chat room (for two users)
    public function startChat(Request $request)
    {
        // dd(env('FIREBASE_CREDENTIALS'));
                $user = auth()->user();
        
        if (!$user->userType) {
            // المستخدم admin
            $user1Name = $user->name;
            $roomId = md5($user1Name . time());
        // dd('admin');
            Room::create([
                'room_id'   => $roomId,
                'user_id'   => null, // أو مش بتحتاجها خالص
                'user_type' => 'admin',
            ]);
        
            return response()->json([
                'roomId'    => $roomId,
                'user_id'   => $user->id,
                'user_type' => 'admin',
            ]);
        
        } else {
            // المستخدم user عادي
            $user1Name = $user->first_name . ' ' . $user->second_name;
            $roomId = md5($user1Name . time());
        
            Room::create([
                'room_id'   => $roomId,
                'user_id'   => $user->id,
                'user_type' =>  $user->userType,
            ]);
        
            return response()->json([
                'roomId'    => $roomId,
                'user_id'   => $user->id,
                'user_type' => $user->userType,
            ]);
        }
    }

    // Send a message to the room User sends message to admin
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'room_id' => 'required|string',
            'receiver_id' => 'required|exists:admins,id',  // Validates user id exists in 'admin' table
        ]);

        // try {
        // Call the service method to send the message
        $this->firebaseService->sendMessage(
            // $roomId,
            $validated['room_id'],
            $validated['message'],
            $validated['receiver_id']
        );

        return response()->json(['success' => true], 200);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        // }
    }

    // Admin sends message to user
    public function sendMessage_admin(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'room_id' => 'required|string',
            'receiver_id' => 'required|exists:users,id',  // Validates user id exists in 'users' table
        ]);

        try {
            // Call the service method to send the message
            $this->firebaseService->sendMessage_admin(
                // $roomId,
                $validated['room_id'],
                $validated['message'],
                $validated['receiver_id']
            );

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    }

    public function getMessages($roomId)
    {
        try {
            $exist_room = Room::where('room_id', $roomId)->first();
            if ($exist_room) {
                // Call the service to get the messages with sender and receiver names
                $messages = $this->firebaseService->getMessages($roomId);

                return response()->json([
                    'room_id' => $roomId,
                    'messages' => $messages,
                ]);
            } else {
                return response()->json(['error' => 'There is no room'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve messages: ' . $e->getMessage()], 500);
        }
    }

    public function getAllRooms()
    {
        // Call the service to get the messages with sender and receiver names
        $messages = $this->firebaseService->getAllRooms();

        return response()->json([
            // 'room_id' => $roomId,
            'messages' => $messages,
        ]);
    }

    public function getUserRooms()
    {
        $userId = Auth::id();

        $rooms = $this->firebaseService->getUserRooms($userId);

        return response()->json([
            'rooms' => $rooms
        ]);
    }

    public function getLatestMessage($roomId)
    {
        $exist_room = Room::where('room_id', $roomId)->first();
        if (!$exist_room) {
            // throw new \Exception("No messages found for room {$roomId}");
            return response()->json(['error' => 'There is no room'], 404);
        }
        $latestMessage = $this->firebaseService->getLatestMessage($roomId);
        // return response()->json($latestMessage);
        return response()->json([
            'room_id' => $roomId,
            'messages' => $latestMessage,
        ]);
    }

    public function closeChat(Request $request, $roomId)
    {
        try {
            $this->firebaseService->closeChat($roomId);
            return response()->json(['message' => 'Chat room closed successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to close chat room: ' . $e->getMessage()], 500);
        }
    }
}
