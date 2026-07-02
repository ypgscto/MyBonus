<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\PresenterCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pmb_can_create_presenter_category_and_logs_audit(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);

        $response = $this->actingAs($user)->post(route('master.presenter-categories.store'), [
            'name' => 'Kategori Baru',
            'description' => 'Deskripsi test',
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('master.presenter-categories.index'));
        $this->assertDatabaseHas('presenter_categories', ['name' => 'Kategori Baru']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'presenter_category_created',
            'module' => 'presenter_category',
        ]);
    }

    public function test_super_admin_can_access_master_presenter_index(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($user)
            ->get(route('master.presenters.index'))
            ->assertOk()
            ->assertSee('Master Presenter');
    }

    public function test_verifikator_cannot_access_master_data(): void
    {
        $user = User::factory()->create(['role' => UserRole::Verifikator]);

        $this->actingAs($user)
            ->get(route('master.presenter-categories.index'))
            ->assertForbidden();
    }

    public function test_toggle_category_status_logs_audit(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create([
            'name' => 'Test Kategori',
            'status' => RecordStatus::Aktif,
        ]);

        $this->actingAs($user)
            ->patch(route('master.presenter-categories.toggle-status', $category))
            ->assertRedirect(route('master.presenter-categories.index'));

        $this->assertDatabaseHas('presenter_categories', [
            'id' => $category->id,
            'status' => 'nonaktif',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'presenter_category_deactivated',
            'module' => 'presenter_category',
            'reference_id' => $category->id,
        ]);
    }

    public function test_presenter_requires_active_category(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create([
            'name' => 'Nonaktif',
            'status' => RecordStatus::Nonaktif,
        ]);

        $this->actingAs($user)->post(route('master.presenters.store'), [
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter A',
            'phone' => '081234567890',
            'email' => 'presenter-a@example.com',
            'status' => 'aktif',
        ])->assertSessionHasErrors('presenter_category_id');
    }

    public function test_commission_scheme_rejects_negative_amount(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = \App\Models\PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'Gelombang 1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);

        $this->actingAs($user)->post(route('master.commission-schemes.store'), [
            'presenter_category_id' => $category->id,
            'pmb_period_id' => $period->id,
            'commission_amount_per_student' => -100,
            'status' => 'aktif',
        ])->assertSessionHasErrors('commission_amount_per_student');
    }

    public function test_default_categories_seeder(): void
    {
        $this->seed(\Database\Seeders\PresenterCategorySeeder::class);

        foreach (['Pegawai', 'Mahasiswa', 'Tamu', 'Vendor'] as $name) {
            $this->assertDatabaseHas('presenter_categories', [
                'name' => $name,
                'status' => 'aktif',
            ]);
        }
    }

    public function test_update_presenter_logs_audit(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $presenter = \App\Models\Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Lama',
            'bank_name' => 'BCA',
            'account_number' => '111',
            'account_holder_name' => 'Lama',
            'phone' => '081234567890',
            'email' => 'lama@example.com',
            'status' => RecordStatus::Aktif,
        ]);

        $this->actingAs($user)->put(route('master.presenters.update', $presenter), [
            'presenter_category_id' => $category->id,
            'name' => 'Baru',
            'bank_name' => 'BCA',
            'account_number' => '111',
            'account_holder_name' => 'Baru',
            'phone' => '081234567890',
            'email' => 'lama@example.com',
            'status' => 'aktif',
        ])->assertRedirect(route('master.presenters.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'presenter_updated',
            'module' => 'presenter',
            'reference_id' => $presenter->id,
        ]);

        $this->assertEquals(1, AuditLog::where('module', 'presenter')->where('action', 'presenter_updated')->count());
    }
}
