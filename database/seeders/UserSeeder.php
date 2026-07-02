<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'bashar.ypgs@gmail.com'],
            [
                'name' => 'Bashar',
                'password' => '12345678',
                'phone' => '628123456789',
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Aktif,
                'must_change_password' => false,
            ]
        );
    }
}
