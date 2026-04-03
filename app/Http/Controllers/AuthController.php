<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $cookie = cookie(
            'jwt_token',
            $token,
            60,
            '/',
            env('JWT_COOKIE_DOMAIN'),
            env('JWT_COOKIE_SECURE'),
            true,
            false,
            env('JWT_COOKIE_SAMESITE')
        );

        return response()->json([
            'user' => Auth::user()
        ])->withCookie($cookie);
    }

    public function logout()
    {
        $cookie = cookie('jwt_token', '', -1, '/');
        return response()->json([
            'status' => 'success',
            'message' => 'Logged out'
        ], 200)->withCookie($cookie);
    }

    public function me()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
        ], 200);
    }
}
