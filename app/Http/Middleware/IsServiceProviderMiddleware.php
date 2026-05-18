<?php

namespace App\Http\Middleware;

use App\Enums\AUserTypeEnum;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class IsServiceProviderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    // public function handle(Request $request, Closure $next): Response
    // {
    //     if (!in_array($request->user()->user_types_id, [AUserTypeEnum::COMPANY, AUserTypeEnum::INDIVIDUAL_BUSINESS])) {
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

        // Deny if user is NOT a service provider or NOT approved
        $isNotAllowed =
            // !in_array($user->user_types_id, [AUserTypeEnum::COMPANY, AUserTypeEnum::INDIVIDUAL_BUSINESS]) ||
            $user->acting_as !== 'provider' ||
            $user->is_approved_provider == 'no';

        if ($isNotAllowed) {
            return response()->json([
                'message' => _('UN_AUTHORIZED')
            ], 403);
        }

        return $next($request);
    }
}
