<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('BONUSKU');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => UserRole::AdminPmb,
            'status' => UserStatus::Aktif,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard.admin-pmb'));
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'status' => UserStatus::Nonaktif,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Keuangan,
            'status' => UserStatus::Aktif,
        ]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_super_admin_can_access_super_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($user)
            ->get(route('dashboard.super-admin'))
            ->assertStatus(200)
            ->assertSee('Dashboard Super Admin');
    }

    public function test_admin_pmb_cannot_access_super_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);

        $this->actingAs($user)
            ->get(route('dashboard.super-admin'))
            ->assertStatus(403);
    }

    public function test_verifikator_cannot_access_keuangan_dashboard(): void
    {
        $user = User::factory()->create(['role' => UserRole::Verifikator]);

        $this->actingAs($user)
            ->get(route('dashboard.keuangan'))
            ->assertStatus(403);
    }

    public function test_inactive_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::AdminPmb,
            'status' => UserStatus::Nonaktif,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.admin-pmb'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_seeded_super_admin_password_is_hashed(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        $user = User::where('email', 'superadmin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(password_verify('password123', $user->password));

        $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard.super-admin'));
    }
}
