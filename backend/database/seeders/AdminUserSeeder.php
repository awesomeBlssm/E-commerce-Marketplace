<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@ecomarket.com'],
            [
                'password_hash' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
                'type' => 'admin',
                'last_login_at' => null,
            ]
        );
    }
}
