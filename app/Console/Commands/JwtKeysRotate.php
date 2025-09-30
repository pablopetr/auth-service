<?php

namespace App\Console\Commands;

use App\Models\JwtKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class JwtKeysRotate extends Command
{
    protected $signature = 'jwt:keys:rotate {--grace-days=1 : Keep old key in JWKS for this many days}';
    protected $description = 'Generate new active key; keep old keys published until grace expires.';

    public function handle(): int
    {
        // Deprecate current active key
        $grace = (int) $this->option('grace-days');
        $old = JwtKey::where('active', true)->first();
        if ($old) {
            $old->update([
                'active' => false,
                'deprecates_at' => now()->addDays($grace),
            ]);
            $this->info("Old active key {$old->kid} will deprecate at {$old->deprecates_at}");
        }

        // Generate new one and mark active
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privPem);
        $pub = openssl_pkey_get_details($res)['key'];

        $kid = Str::uuid()->toString();

        JwtKey::create([
            'kid' => $kid,
            'public_pem' => $pub,
            'private_pem_encrypted' => Crypt::encryptString($privPem),
            'active' => true,
            'deprecates_at' => null,
        ]);

        $this->info("New ACTIVE key kid: {$kid}");
        return self::SUCCESS;
    }
}
