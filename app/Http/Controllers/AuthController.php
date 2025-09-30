<?php

namespace App\Http\Controllers;

use App\Models\RefreshToken;
use App\Models\User;
use App\Services\Jwt\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $data = $request->validate([
           'email' => ['required', 'email:rfc,dns', 'unique:users,email'],
          'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password'],
            'token_version' => 1,
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function login(Request $request, JwtService $jwt)
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc,dns'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $access = $jwt->generateAccessToken($user);
        $refresh = $this->issueRefreshToken($user, $request);

        return response()->json([
            'access_token' => $access['token'],
            'expires_in' => $access['expires_in'],
            'refresh_token' => $refresh,
            'token_type' => 'Bearer',
        ]);
    }

    public function refresh(Request $request, JwtService $jwt)
    {
        $data = $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $plain = $data['refresh_token'];
        $now = now();

        $candidate = RefreshToken::whereNull('revoked_at')
            ->where('expires_at', '>', $now)
            ->latest('id')
            ->get()
            ->first(function ($row) use($plain) {
                return Hash::check($plain, $row->token_hash);
            });

        if(!$candidate) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = $candidate->user;

        $idempotencyWindow = 10;

        if($candidate->last_used_at && $candidate->last_used_at->gt($now->copy()->subSeconds($idempotencyWindow))) {
            $access = app(JwtService::class)->generateAccessToken($user);

            return response()->json([
                'access_token' => $access['token'],
                'expires_in' => $access['expires_in'],
                'refresh_token' => $plain,
                'token_type' => 'Bearer',
            ]);
        }

        $candidate->update(['revoked_at' => $now, 'last_used_at' => $now]);

        $access = $jwt->generateAccessToken($user);
        $newRefresh = $this->issueRefreshToken($user, $request);

        return response()->json([
            'access_token' => $access['token'],
            'expires_in' => $access['expires_in'],
            'refresh_token' => $newRefresh,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $data = $request->validate([
           'refresh_token' => ['required', 'string'],
        ]);

        $plain = $data['refresh_token'];
        $now = now();

        $candidate = RefreshToken::whereNull('revoked_at')
            ->where('expires_at', '>', $now)
            ->latest('id')
            ->get()
            ->first(function ($row) use($plain) {
                return Hash::check($plain, $row->token_hash);
            });

        if($candidate) {
            $candidate->update(['revoked_at' => $now, 'last_used_at' => $now]);
        }

        return response()->json(['ok' => true]);
    }

    private function issueRefreshToken(User $user, Request $request): string
    {
        $ttlDays = (int) config('jwt.refresh_ttl_days', 14);

        $plain = Str::random(64) . Str::random(64);
        $hash = Hash::make($plain);

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'expires_at' => now()->addDays($ttlDays),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'ip' => (string) $request->ip(),
        ]);

        return $plain;
    }
}
