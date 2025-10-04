<?php

namespace App\Http\Controllers\Admin;

use App\Enum\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Jwt\JwtService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __invoke(Request $request, JwtService $jwt)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])
            ->where('role', UserRole::Admin->value)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (! $user || ! password_verify($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $adminScopes = [
            'admin',
            'podcasts:read',
            'podcasts:write',
            'episodes:read',
            'episodes:write',
            'episodes:publish',
        ];

        $aud = ['podcasts'];

        $token = $jwt->generateAccessToken(
            user: $user,
            aud: $aud,
            scope: $adminScopes,
        );

        return response()->json([
            'access_token' => $token['token'],
            'expires_in' => $token['expires_in'],
            'token_type' => 'Bearer',
            'scopes' => $adminScopes,
        ]);
    }
}
