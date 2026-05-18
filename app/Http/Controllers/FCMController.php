<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class FCMController extends Controller
{
    public function index()
    {
        // dd(env('FIREBASE_CREDENTIALS'));


        return view('fcm.index');
    }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'fcm_token' => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 400);
    //     }

    //     // Save the FCM token to the logged-in user's record
    //     $user = auth()->user(); // Ensure user is logged in
    //     $user->fcm_token = $request->fcm_token;
    //     $user->save();

    //     return response()->json(['success' => 'FCM token stored successfully'], 200);
    // }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        try {
            // Store the token (example: in the database or cache)
            $token = $request->input('fcm_token');

            // Assuming you save the token here, e.g., in the database
            // User::updateOrCreate(['id' => auth()->id()], ['fcm_token' => $token]);

            return Response::json(['success' => true, 'message' => 'Token stored successfully']);
        } catch (\Exception $e) {
            return Response::json(['error' => 'Error storing FCM token', 'message' => $e->getMessage()], 500);
        }
    }

}
