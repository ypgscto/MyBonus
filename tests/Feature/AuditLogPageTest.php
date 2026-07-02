<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_audit_log_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'module' => 'auth',
            'reference_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('Audit Log')
            ->assertSee('Login');
    }

    public function test_super_admin_can_filter_audit_log_by_action(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Login->value,
            'module' => 'auth',
            'reference_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => AuditAction::Logout->value,
            'module' => 'auth',
            'reference_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index', ['action' => AuditAction::Login->value]))
            ->assertOk()
            ->assertSee('127.0.0.1')
            ->assertDontSee('10.0.0.1');
    }

    public function test_admin_pmb_cannot_access_audit_log_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }
}
