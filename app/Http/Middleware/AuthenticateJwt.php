<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Jwt\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private JwtService $jwt)
    {

    }

    public function handle(Request $request, Closure $next, ?string $requiredAud = null): Response
    {
        $hdr = $request->bearerToken();

        if(!$hdr) {
            return response()->json(['message' => 'Missing bearer token'], 401);
        }

        try {
            $response = $this->jwt->validate($hdr, $requiredAud);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid bearer token'], 401);
        }

        $claims = $response['claims'];
        $uid = (int) ($claims['sub'] ?? 0);
        $ver = (int) ($claims['ver'] ?? 0);

        $user = User::find($uid);
        if (!$user) return response()->json(['message' => 'User not found'], 401);
        if ($user->token_version !== $ver) {
            return response()->json(['message' => 'Token version mismatch'], 401);
        }

        $request->attributes->set('auth_user', $user);
        $request->attributes->set('auth_claims', $claims);

        return $next($request);
    }
}
