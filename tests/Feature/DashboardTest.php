<?php

namespace Tests\Feature;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PmbPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_shows_statistics(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1',
            'account_holder_name' => 'A',
            'phone' => '081234567890',
            'status' => RecordStatus::Aktif,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.super-admin'))
            ->assertOk()
            ->assertSee('Total Presenter Aktif')
            ->assertSee('Grafik Permintaan per Bulan')
            ->assertSee('Top 10 Presenter');
    }

    public function test_admin_pmb_dashboard_shows_own_request_counts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $other = User::factory()->create(['role' => UserRole::AdminPmb]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'G1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);
        $presenter = Presenter::create([
            'presenter_category_id' => PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif])->id,
            'name' => 'P',
            'bank_name' => 'BCA',
            'account_number' => '1',
            'account_holder_name' => 'P',
            'phone' => '081',
            'status' => RecordStatus::Aktif,
        ]);

        PresenterRequest::create([
            'request_code' => 'PR-202607-0001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::Draft,
            'request_date' => now()->toDateString(),
        ]);
        PresenterRequest::create([
            'request_code' => 'PR-202607-0002',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $other->id,
            'status' => PresenterRequestStatus::Draft,
            'request_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.admin-pmb'))
            ->assertOk()
            ->assertSee('Riwayat Permintaan Terbaru')
            ->assertSee('1', false);
    }

    public function test_verifikator_dashboard_shows_pending_list(): void
    {
        $user = User::factory()->create(['role' => UserRole::Verifikator]);

        $this->actingAs($user)
            ->get(route('dashboard.verifikator'))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Total Nominal Transfer');
    }

    public function test_keuangan_dashboard_shows_finance_stats(): void
    {
        $user = User::factory()->create(['role' => UserRole::Keuangan]);

        $this->actingAs($user)
            ->get(route('dashboard.keuangan'))
            ->assertOk()
            ->assertSee('Dana Masuk')
            ->assertSee('Total Pencairan Bulan Ini');
    }
}
