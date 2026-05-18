<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use App\Models\Wallet;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AppleAuthController extends Controller
{
    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('code', $code)->exists());

        return $code;
    }

    // public function appleSignIn(Request $request)
    // {
    //     $request->validate([
    //         'identityToken' => 'required|string',
    //         'email' => 'nullable|email',
    //         'name' => 'nullable|string',
    //         'fcm_token' => 'nullable|string',
    //     ]);

    //     $fcm_token = $request->fcm_token;

    //     // Get Apple public keys
    //     $keys = Http::get('https://appleid.apple.com/auth/keys')->json();
    //     $publicKeys = JWK::parseKeySet($keys);

    //     //  Decode & verify token
    //     $decoded = JWT::decode($request->identityToken, $publicKeys);

    //     $appleId = $decoded->sub;
    //     $email = $decoded->email ?? $request->email;

    //     // Find or create user
    //     $user = User::firstOrCreate(
    //         ['apple_id' => $appleId],
    //         [
    //             'email' => $email,
    //             'name' => $request->name ?? null,
    //             'fcm_token' => $fcm_token,
    //             'code' => $this->generateUniqueCode(),
    //         ]
    //     );

    //      $is_deactive = User::where('email', $user->email)->first()->is_deactive;
    //         if($is_deactive == 1){
    //             return response()->json([
    //             'message' => 'You are deactivated',
    //             // 'message_ar' => 'فشل إنشاء رمز التحقق',
    //             ], 401);
    //         }

    //     $cart = Cart::firstOrCreate(
    //         ['user_id' => $user->id],
    //         ['total_price' => 0.0],
    //         ['old_price' => 0.0]
    //     );
    //     $wallet = Wallet::firstOrCreate(
    //         ['user_id' => $user->id],
    //         ['balance' => 0]
    //     );

    //     // Create Sanctum token
    //     $token = $user->createToken('mobile')->plainTextToken;

    //     return response()->json([
    //         'token' => $token,
    //         'user' => $user,
    //     ]);
    // }

    public function appleSignIn(Request $request)
    {
        $request->validate([
            'identityToken' => 'required|string',
            'email' => 'nullable|email',
            'name' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ]);

        $fcm_token = $request->fcm_token;

        // Get Apple public keys
        $keys = Http::get('https://appleid.apple.com/auth/keys')->json();
        $publicKeys = JWK::parseKeySet($keys);

        // Decode & verify token
        $decoded = JWT::decode($request->identityToken, $publicKeys);

        $appleId = $decoded->sub;
        $email = $decoded->email ?? $request->email;

        // Find user by apple_id or email
        $user = User::where('apple_id', $appleId)->orWhere('email', $email)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'apple_id' => $appleId,
                'email' => $email,
                'name' => $request->name ?? null,
                'fcm_token' => $fcm_token,
                'code' => $this->generateUniqueCode(),
            ]);
        } else {
            // Update existing user
            if (!$user->apple_id) {
                $user->apple_id = $appleId;
            }

            // ❗ تحديث FCM token لو مختلف أو موجود
            if ($fcm_token && $user->fcm_token !== $fcm_token) {
                $user->fcm_token = $fcm_token;
            }

            $user->save();
        }

        if ($user->is_deactive) {
            return response()->json([
                'message' => 'You are deactivated',
            ], 401);
        }

        // Ensure Cart and Wallet exist
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id],
            ['total_price' => 0.0, 'old_price' => 0.0]
        );

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
        
        // $order = Order::create([
        //         ['user_id' => $user->id],
        //         ['total' => 0],  // will update this later after adding items
        //         ['status' => 'null']
        //     ]);

        // Create Sanctum token
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
