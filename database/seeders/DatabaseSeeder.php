<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'pabloeliezer@hotmail.com'],
            ['name' => 'John Doe', 'password' => bcrypt('password'), 'token_version' => 1],
        );
    }
}
