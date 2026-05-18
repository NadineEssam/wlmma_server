<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\User;
use App\Models\Wallet;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Laravel\Socialite\Facades\Socialite;
use Google_Client;

class GoogleController extends Controller
{
    // Redirect to Google
    public function redirectToGoogle()
    {
        Log::info('Redirecting to Google OAuth');
        return Socialite::driver('google')
            ->stateless()
            ->with([
                'redirect_uri' => config('services.google.redirect'),  // تأكد من أن `config/services.php` صحيح
            ])
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('code', $code)->exists());

        return $code;
    }

    // Handle Google Callback
    public function handleGoogleCallback(Request $request)
    {
        try {
            Log::info('Callback Request Data:', $request->all());

            $code = $request->get('code');
            if (!$code) {
                Log::error('Authorization code is missing');
                return response()->json([
                    'error' => 'Authorization code is missing',
                    'error_ar' => 'رمز التفويض مفقود',
                ], 400);
            }

            $googleUser = Socialite::driver('google')->stateless()->user();
            // Log::info('Google User Data:', (array) $googleUser);

            // Check if the user already exists
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                // Create a new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => bcrypt(Str::random(16)),  // Generate a random password
                    'google_id' => $googleUser->id,
                    'code' => $this->generateUniqueCode(),
                ]);
            }

            // Generate a token for API authentication
            $token = $user->createToken('GoogleToken')->plainTextToken;

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            // \Log::error('Google OAuth Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Google authentication failed',
                'error_ar' => 'فشلت مصادقة جوجل',
            ], 500);
        }
    }

    // For Backend
    public function googleSignIn_WEB(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $idToken = $request->id_token;

        // 1) Verify the token with Google
        $googleResponse = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$idToken}");

        if ($googleResponse->failed()) {
            return response()->json([
                'error' => 'Invalid Google ID token',
                'error_ar' => 'رمز Google غير صالح',
            ], 401);
        }

        $googleData = $googleResponse->json();

        // 2) Extract user info
        $email = $googleData['email'] ?? null;
        $name = $googleData['name'] ?? null;
        $googleId = $googleData['sub'] ?? null;

        if (!$email) {
            return response()->json([
                'error' => 'Google returned no email',
            ], 400);
        }

        // 3) Check if user exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            // 4) Create user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        // 5) Issue Laravel Sanctum token
        $token = $user->createToken('google-login')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function googleSignIn(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
            'fcm_token' => 'required|string',
        ]);

        $idToken = $request->id_token;

        Log::info('Received ID Token (first 50 chars): ' . substr($idToken, 0, 50));

        // 1) تحقق من طول التوكن
        if (strlen($idToken) < 100) {
            return response()->json([
                'error' => 'Token too short',
                'error_ar' => 'الرمز قصير جداً',
                'token_length' => strlen($idToken),
            ], 400);
        }

        // 2) استدعاء Google للتحقق
        $response = Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$idToken}");

        Log::info('Google Response Status: ' . $response->status());
        Log::info('Google Response Body: ' . $response->body());

        if ($response->failed()) {
            $errorData = $response->json();

            return response()->json([
                'error' => 'Google token verification failed',
                'error_ar' => 'فشل التحقق من رمز Google',
                'google_error' => $errorData['error_description'] ?? $errorData['error'] ?? 'Unknown',
                'status_code' => $response->status(),
            ], 401);
        }

        $googleData = $response->json();

        // 3) سجل كل البيانات الواردة من Google للتفحص
        Log::info('Google Token Info:', $googleData);

        // 4) تحقق من الحقول الأساسية
        if (!isset($googleData['email'])) {
            return response()->json([
                'error' => 'No email in Google response',
                'error_ar' => 'لا يوجد بريد إلكتروني في استجابة Google',
                'google_data' => $googleData,
            ], 400);
        }

        // 3) Extract user info
        $email = $googleData['email'] ?? null;
        $name = $googleData['name'] ?? null;
        $googleId = $googleData['sub'] ?? null;
        $fcm_token = $request->fcm_token;

        // if (!$email) {
        //     return response()->json([
        //         'error' => 'Google returned no email',
        //     ], 400);
        // }

        // 4) Check if user exists

        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create user
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'password' => bcrypt(Str::random(16)),
                'fcm_token' => $fcm_token,
            ]);

            $cart = Cart::create(
                ['user_id' => $user->id],
                ['total_price' => 0.0],
                ['old_price' => 0.0]
            );
            $wallet = Wallet::create(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // $order = Order::create([
            //     ['user_id' => $user->id],
            //     ['total' => 0],  // will update this later after adding items
            //     ['status' => 'null']
            // ]);
        } else {
            $is_deactive = User::where('email', $email)->first()->is_deactive;
            if ($is_deactive == 1) {
                return response()->json([
                    'message' => 'You are deactivated',
                    // 'message_ar' => 'فشل إنشاء رمز التحقق',
                ], 401);
            }

            // ❗ تحديث google_id لو مش محفوظ
            if (!$user->google_id) {
                $user->google_id = $googleId;
            }

            // ❗ تحديث FCM token لكل المستخدمين حتى لو موجود مسبقاً
            if ($user->fcm_token !== $fcm_token) {
                $user->fcm_token = $fcm_token;
            }

            $user->save();
            // ❗ مهم: حدّث google_id لو مش محفوظ

            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['totl_price' => 0.0],
                ['old_price' => 0.0]
            );
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );
        }

        // 5) Issue Laravel Sanctum token
        $token = $user->createToken('google-login')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
}
