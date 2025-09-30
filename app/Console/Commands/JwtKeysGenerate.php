<?php

namespace App\Console\Commands;

use App\Models\JwtKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class JwtKeysGenerate extends Command
{
    protected $signature = 'jwt:keys:generate {--activate : Mark this key as active signer} {--deprecate-days=0}';
    protected $description = 'Generate RSA key pair, store encrypted private key and public key, optionally activate.';

    public function handle(): int
    {
        $kid = Str::uuid()->toString();

        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privPem);
        $details = openssl_pkey_get_details($res);
        $pubPem = $details['key'];

        $encrypted = Crypt::encryptString($privPem);

        $active = $this->option('activate') ? true : false;
        if ($active) {
            JwtKey::where('active', true)->update(['active' => false]);
        }

        $deprecatesAt = null;
        $days = (int) $this->option('deprecate-days');
        if ($days > 0) {
            $deprecatesAt = now()->addDays($days);
        }

        JwtKey::create([
            'kid' => $kid,
            'public_pem' => $pubPem,
            'private_pem_encrypted' => $encrypted,
            'active' => $active,
            'deprecates_at' => $deprecatesAt,
        ]);

        $this->info("kid: {$kid}");
        if ($active) $this->info('This key is ACTIVE for signing.');
        return self::SUCCESS;
    }
}
