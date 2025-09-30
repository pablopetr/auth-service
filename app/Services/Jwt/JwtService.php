<?php

namespace App\Services\Jwt;

use App\Models\JwtKey;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class JwtService
{
    /** Generates a compact JWS RS256 with a selected active kid. */
    public function generateAccessToken(User $user, array $aud = null, array $scope = []): array
    {
        $kid = $this->getActiveKid();
        [$privPem, $pubPem] = $this->getKeyPairByKid($kid);

        $now = time();
        $ttl = (int) config('jwt.access_ttl_minutes', 10) * 60;

        $payload = [
            'iss' => config('jwt.issuer'),
            'aud' => $aud ?? config('jwt.default_audiences'),
            'sub' => (string) $user->id,
            'ver' => (int) $user->token_version,
            'scope' => array_values($scope),
            'iat' => $now,
            'nbf' => $now - 0,
            'exp' => $now + $ttl,
        ];

        $jwt = $this->signJwt($payload, $privPem, $kid);

        return [
            'token' => $jwt,
            'kid' => $kid,
            'expires_in' => $ttl,
            'public_pem' => $pubPem, // pode ser útil internamente (não retornar em API)
        ];
    }

    /** Returns [privatePem, publicPem] for kid */
    public function getKeyPairByKid(string $kid): array
    {
        $key = JwtKey::where('kid', $kid)->firstOrFail();
        $priv = Crypt::decryptString($key->private_pem_encrypted);
        return [$priv, $key->public_pem];
    }

    /** Gets current active kid (cached). */
    public function getActiveKid(): string
    {
        return Cache::remember('jwt_active_kid', 60, function () {
            $key = JwtKey::where('active', true)->latest('id')->first();
            if (!$key) {
                throw new RuntimeException('No active JWT key. Run: php artisan jwt:keys:generate');
            }
            return $key->kid;
        });
    }

    /** Validates signature+claims; returns ['ok'=>true, 'claims'=>[]] or throws RuntimeException */
    public function validate(string $jwt, ?string $requiredAud = null): array
    {
        [$header, $payload, $sig] = $this->splitJwt($jwt);

        $kid = $header['kid'] ?? null;
        if (!$kid) throw new RuntimeException('Missing kid');

        $pubPem = $this->getPublicPemByKid($kid);

        // Verify signature (RS256)
        $signed = $this->b64($header, $payload);
        $ok = openssl_verify($signed, $this->b64d($sig), $pubPem, OPENSSL_ALGO_SHA256);
        if ($ok !== 1) throw new RuntimeException('Invalid signature');

        // Validate claims
        $skew = (int) config('jwt.clock_skew', 60);
        $now = time();

        $iss = $payload['iss'] ?? null;
        $aud = $payload['aud'] ?? null;
        $exp = $payload['exp'] ?? null;
        $nbf = $payload['nbf'] ?? null;
        $iat = $payload['iat'] ?? null;

        if (!$iss || $iss !== config('jwt.issuer')) throw new RuntimeException('Bad iss');
        if (!$aud) throw new RuntimeException('Missing aud');
        if ($requiredAud && !in_array($requiredAud, (array)$aud, true)) throw new RuntimeException('Bad aud');
        if (!$exp || ($now - $skew) >= $exp) throw new RuntimeException('Expired');
        if ($nbf && ($now + $skew) < $nbf) throw new RuntimeException('Not yet valid');
        if ($iat && ($iat - $skew) > $now) throw new RuntimeException('Bad iat');

        return ['ok' => true, 'claims' => $payload, 'kid' => $kid];
    }

    /** Returns public PEM for kid (cached 15m). */
    public function getPublicPemByKid(string $kid): string
    {
        return Cache::remember("jwt_pub_{$kid}", 15 * 60, function () use ($kid) {
            $key = JwtKey::where('kid', $kid)->firstOrFail();
            return $key->public_pem;
        });
    }

    // ===== Helpers =====
    private function signJwt(array $payload, string $privatePem, string $kid): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $kid];
        $segments = [
            $this->b64e(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64e(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signingInput = implode('.', $segments);
        $pkey = openssl_pkey_get_private($privatePem);
        if (!$pkey) throw new RuntimeException('Invalid private key');
        $ok = openssl_sign($signingInput, $signature, $pkey, OPENSSL_ALGO_SHA256);
        if (!$ok) throw new RuntimeException('Sign failed');
        return $signingInput.'.'.$this->b64e($signature);
    }

    private function splitJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) throw new RuntimeException('Malformed token');
        $header = json_decode($this->b64d($parts[0]), true) ?: [];
        $payload = json_decode($this->b64d($parts[1]), true) ?: [];
        return [$header, $payload, $parts[2]];
    }

    private function b64(array|string $h, array|string $p): string
    {
        $h64 = is_array($h) ? $this->b64e(json_encode($h)) : $h;
        $p64 = is_array($p) ? $this->b64e(json_encode($p)) : $p;
        return $h64.'.'.$p64;
    }

    private function b64e(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function b64d(string $b64): string
    {
        $p = strlen($b64) % 4;
        if ($p) $b64 .= str_repeat('=', 4 - $p);
        return base64_decode(strtr($b64, '-_', '+/'));
    }
}
