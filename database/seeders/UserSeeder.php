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
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => 'password123',
                'phone' => '081234567890',
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Aktif,
            ],
            [
                'name' => 'Admin PMB',
                'email' => 'adminpmb@example.com',
                'password' => 'password123',
                'phone' => '081234567891',
                'role' => UserRole::AdminPmb,
                'status' => UserStatus::Aktif,
            ],
            [
                'name' => 'Verifikator',
                'email' => 'verifikator@example.com',
                'password' => 'password123',
                'phone' => '081234567892',
                'role' => UserRole::Verifikator,
                'status' => UserStatus::Aktif,
            ],
            [
                'name' => 'Keuangan',
                'email' => 'keuangan@example.com',
                'password' => 'password123',
                'phone' => '081234567893',
                'bank_name' => 'BRI',
                'account_number' => '1234567890',
                'account_holder_name' => 'Keuangan STIKES GS',
                'role' => UserRole::Keuangan,
                'status' => UserStatus::Aktif,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
