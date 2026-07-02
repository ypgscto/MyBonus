<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_user_management(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Kelola User');
    }

    public function test_non_super_admin_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_user_with_whatsapp(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Verifikator Baru',
                'email' => 'verif.new@example.com',
                'phone' => '081298765432',
                'role' => UserRole::Verifikator->value,
                'status' => UserStatus::Aktif->value,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'verif.new@example.com',
            'phone' => '6281298765432',
            'role' => UserRole::Verifikator->value,
        ]);
    }

    public function test_user_without_whatsapp_cannot_be_saved(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'User Invalid',
                'email' => 'invalid@example.com',
                'phone' => '',
                'role' => UserRole::Keuangan->value,
                'status' => UserStatus::Aktif->value,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('phone');
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->patch(route('users.toggle-status', $superAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(UserStatus::Aktif, $superAdmin->fresh()->status);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::AdminPmb,
            'status' => UserStatus::Nonaktif,
            'email' => 'inactive@example.com',
        ]);

        $this->post(route('login'), [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_edit_user_without_password_keeps_old_password(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $user = User::factory()->keuangan()->create(['phone' => '628111111111']);
        $oldPasswordHash = $user->password;

        $this->actingAs($superAdmin)
            ->put(route('users.update', $user), [
                'name' => 'Keuangan Updated',
                'email' => $user->email,
                'phone' => '628122222222',
                'role' => UserRole::Keuangan->value,
                'status' => UserStatus::Aktif->value,
                'bank_name' => $user->bank_name,
                'account_number' => $user->account_number,
                'account_holder_name' => $user->account_holder_name,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Keuangan Updated', $user->name);
        $this->assertSame('628122222222', $user->phone);
        $this->assertSame($oldPasswordHash, $user->password);
    }

    public function test_super_admin_can_create_keuangan_user_with_bank_account(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Bendahara',
                'email' => 'bendahara@example.com',
                'phone' => '081299988877',
                'role' => UserRole::Keuangan->value,
                'status' => UserStatus::Aktif->value,
                'bank_name' => 'Mandiri',
                'account_number' => '9876543210',
                'account_holder_name' => 'Bendahara PMB',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'bendahara@example.com',
            'bank_name' => 'Mandiri',
            'account_number' => '9876543210',
            'account_holder_name' => 'Bendahara PMB',
        ]);
    }

    public function test_keuangan_user_requires_bank_fields(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Bendahara Invalid',
                'email' => 'bendahara.invalid@example.com',
                'phone' => '081299988878',
                'role' => UserRole::Keuangan->value,
                'status' => UserStatus::Aktif->value,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors(['bank_name', 'account_number', 'account_holder_name']);
    }
}
