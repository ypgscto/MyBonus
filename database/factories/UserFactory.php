<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password123'),
            'phone' => '08'.fake()->numerify('##########'),
            'role' => UserRole::AdminPmb,
            'status' => UserStatus::Aktif,
            'remember_token' => Str::random(10),
        ];
    }

    public function keuangan(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Keuangan,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => fake()->name(),
        ]);
    }
}
