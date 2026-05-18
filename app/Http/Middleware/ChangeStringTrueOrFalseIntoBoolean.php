<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChangeStringTrueOrFalseIntoBoolean
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $newRequest = $this->convertStringBooleans($request);
        return $next($newRequest);
    }

    /**
     * Convert "true" or "false" strings to boolean true or false.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function convertStringBooleans($request)
    {
        $fields = $request->all();

        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                if (strtolower($value) === 'true') {
                    $request->merge([$key => true]);
                } elseif (strtolower($value) === 'false') {
                    $request->merge([$key => false]);
                }
            }
        }
        return $request;
    }
}
