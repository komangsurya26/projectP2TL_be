<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckJwtCookie
{
    public function handle($request, Closure $next)
    {
        $token = $request->cookie('jwt_token');

        if (!$token) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            JWTAuth::setToken($token)->authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
