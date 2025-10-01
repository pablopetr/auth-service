<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $premium = User::firstOrCreate(
            ['email' => 'premium@user.com'],
            ['name' => 'Premium User', 'password' => bcrypt('password'), 'token_version' => 1],
        );

        User::firstOrCreate(
            ['email' => 'basic@user.com'],
            ['name' => 'Basic User', 'password' => bcrypt('password'), 'token_version' => 1],
        );

        Membership::factory()->create([
            'user_id' => $premium->id,
            'ends_at' => now()->addMonth(),
            'ended_at' => null,
        ]);
    }
}
