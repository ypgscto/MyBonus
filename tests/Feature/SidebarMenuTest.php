<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Presenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sidebar_shows_admin_menus(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($user)
            ->get(route('dashboard.super-admin'))
            ->assertSee('Kelola User')
            ->assertSee('Permintaan Presenter')
            ->assertSee('Buat Permintaan')
            ->assertSee('Semua Permintaan')
            ->assertSee('Laporan')
            ->assertDontSee('Permintaan Keuangan');
    }

    public function test_admin_pmb_sidebar_shows_presenter_request_menus(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);

        $this->actingAs($user)
            ->get(route('dashboard.admin-pmb'))
            ->assertSee('Permintaan Presenter')
            ->assertSee('Buat Permintaan')
            ->assertSee('Draft Permintaan')
            ->assertSee('Riwayat Permintaan')
            ->assertDontSee('Kelola User');
    }

    public function test_keuangan_sidebar_shows_finance_menus(): void
    {
        $user = User::factory()->create(['role' => UserRole::Keuangan]);

        $this->actingAs($user)
            ->get(route('dashboard.keuangan'))
            ->assertSee('Dana Masuk')
            ->assertSee('Transfer ke Presenter')
            ->assertSee('Riwayat Pencairan')
            ->assertSee('Laporan Keuangan')
            ->assertDontSee('Buat Permintaan')
            ->assertDontSee('Kelola User');
    }

    public function test_verifikator_sidebar_shows_verifikator_menus(): void
    {
        $user = User::factory()->create(['role' => UserRole::Verifikator]);

        $this->actingAs($user)
            ->get(route('dashboard.verifikator'))
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Disetujui')
            ->assertSee('Ditolak')
            ->assertSee('Transfer ke Keuangan')
            ->assertDontSee('Buat Permintaan');
    }

    public function test_presenter_sidebar_shows_presenter_menus(): void
    {
        $user = User::factory()->create(['role' => UserRole::Presenter, 'must_change_password' => false]);
        Presenter::create([
            'user_id' => $user->id,
            'presenter_category_id' => \App\Models\PresenterCategory::create(['name' => 'Pegawai', 'status' => \App\Enums\RecordStatus::Aktif])->id,
            'name' => 'Presenter Sidebar',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Sidebar',
            'phone' => '081234567895',
            'email' => 'sidebar.presenter@example.com',
            'status' => \App\Enums\RecordStatus::Aktif,
        ]);

        $this->actingAs($user)
            ->get(route('presenter.dashboard'))
            ->assertSee('Mahasiswa Saya')
            ->assertSee('Status Pencairan')
            ->assertDontSee('Kelola User');
    }
}
