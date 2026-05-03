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
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_ADMIN,
                'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
                'email_verified_at' => now(),
            ],
        );
    }
}
