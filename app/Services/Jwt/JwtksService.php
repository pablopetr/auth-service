<?php

namespace App\Services\Jwt;

use App\Models\JwtKey;

class JwtksService
{
    /** Returns JWKS array: ['keys' => [ ... ]] */
    public function jwks(): array
    {
        $keys = JwtKey::query()
            ->where(function ($q) {
                $q->where('active', true)
                    ->orWhereNull('deprecates_at')
                    ->orWhere('deprecates_at', '>', now());
            })
            ->get();

        $jwkKeys = [];
        foreach ($keys as $key) {
            $jwkKeys[] = $this->toJwk($key->kid, $key->public_pem);
        }

        return ['keys' => $jwkKeys];
    }

    private function toJwk(string $kid, string $publicPem): array
    {
        $res = openssl_pkey_get_public($publicPem);
        $det = openssl_pkey_get_details($res);
        $n = $det['rsa']['n'] ?? null;
        $e = $det['rsa']['e'] ?? null;
        $b64url = fn ($bin) => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

        return [
            'kid' => $kid,
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'n' => $b64url($n),
            'e' => $b64url($e),
        ];
    }
}
