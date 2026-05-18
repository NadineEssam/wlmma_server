<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserType
{
    public function handle(Request $request, Closure $next, $type)
    {
        // Get the authenticated user
        $user = $request->user();

        // Check if the user's type matches the required type
        if ($user && $user->userType->type === $type) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }
}
