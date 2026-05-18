<?php

namespace App\Http\Middleware;

use App\Enums\AUserTypeEnum;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class IsUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     if (!in_array($request->user()->user_types_id, [AUserTypeEnum::USER])) {
    //         return response()->json(
    //             [
    //                 'message' => _('UN_AUTHORIZED')
    //             ],
    //             403
    //         );
    //     }
    //     return $next($request);
    // }
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isNotAllowed = //$user->user_types_id !== AUserTypeEnum::USER ||
            $user->acting_as == 'provider' ;

        if ($isNotAllowed) {
            return response()->json([
                'message' => _('UN_AUTHORIZED')
            ], 403);
        }

        return $next($request);
    }
}
