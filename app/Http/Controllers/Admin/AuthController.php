<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\Admin\LoginAdminRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct() {}

    public function login(LoginAdminRequest $request)
    {
        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            throw ValidationException::withMessages([
                'error' => 'The provided credentials are incorrect.',
            ]);
        }
        // $admin->tokens()->delete();

        return response()->json([
            'data' => new AdminResource($admin),
            'meta' => [
                'token' => $admin->createToken($request->email)->plainTextToken,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $admin = auth('admins')->user();

        if (!$admin) {
            return response()->json([
                'error' => 'Unauthenticated',
                'error_ar' => 'غير مصادق عليه',
            ], 401);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            // 'message' => _('SUCCESS')
            'message' => 'Success',
            'message_ar' => 'نجاح',
        ], 204);
    }

    /**
     * Send reset link email
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:admins,email',
        ]);

        $token = Str::random(64);

        \DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // You can use Laravel’s Mail facade or Notification here
        // For simplicity, we’ll just return the token in JSON (for API use)
        // In production, send an email with this token link
        // e.g., https://your-admin-panel.com/reset-password?token=xxxx

        return response()->json([
            'message' => 'Password reset link sent successfully.',
            'token' => $token,  // remove this in production
        ]);
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:admins,email',
            'token' => 'required|string',
            'password' => 'required|confirmed|min:12',
        ]);

        $record = \DB::table('password_resets')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['error' => 'Invalid or expired token.'], 400);
        }

        Admin::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        // Delete the token after successful reset
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.',
        ]);
    }
}
