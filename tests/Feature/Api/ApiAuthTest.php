<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 'v1');
    }

    public function test_user_can_login_via_api_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
            'password' => 'password123',
            'role' => UserRole::Verifikator,
            'status' => UserStatus::Aktif,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@example.com',
            'password' => 'password123',
            'device_name' => 'flutter-test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'api@example.com')
            ->assertJsonPath('data.user.role', UserRole::Verifikator->value);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'flutter-test',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_inactive_user_cannot_login_via_api(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'status' => UserStatus::Nonaktif,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Keuangan,
            'status' => UserStatus::Aktif,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', UserRole::Keuangan->value);
    }

    public function test_unauthenticated_api_request_returns_json_401(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::AdminPmb,
            'status' => UserStatus::Aktif,
        ]);

        $token = $user->createToken('test-device')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_presenter_with_must_change_password_is_blocked_from_other_endpoints(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Presenter,
            'status' => UserStatus::Aktif,
            'must_change_password' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/presenter/dashboard')
            ->assertForbidden()
            ->assertJsonPath('code', 'MUST_CHANGE_PASSWORD');
    }

    public function test_role_middleware_blocks_wrong_role(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Presenter,
            'status' => UserStatus::Aktif,
            'must_change_password' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/verifikator/requests')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }
}
