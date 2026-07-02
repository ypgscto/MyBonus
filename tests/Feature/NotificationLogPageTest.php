<?php

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\NotificationLog;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PmbPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_notification_log_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'Gelombang 1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);
        $presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter A',
            'phone' => '081234567890',
            'status' => RecordStatus::Aktif,
        ]);
        $request = PresenterRequest::create([
            'request_code' => 'PR-202607-0001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now()->toDateString(),
        ]);
        NotificationLog::create([
            'presenter_request_id' => $request->id,
            'recipient_role' => UserRole::Verifikator->value,
            'recipient_name' => 'Verifikator',
            'recipient_phone' => '081111111111',
            'message' => 'Permintaan baru menunggu verifikasi',
            'provider' => 'kirimi',
            'status' => NotificationStatus::Sent,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.notification-logs.index'))
            ->assertOk()
            ->assertSee('Notification Log')
            ->assertSee('PR-202607-0001')
            ->assertSee('kirimi');
    }

    public function test_super_admin_can_filter_notification_log_by_request_code(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'Gelombang 1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);
        $presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter A',
            'phone' => '081234567890',
            'status' => RecordStatus::Aktif,
        ]);
        $requestA = PresenterRequest::create([
            'request_code' => 'PR-202607-0001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now()->toDateString(),
        ]);
        $requestB = PresenterRequest::create([
            'request_code' => 'PR-202607-0099',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now()->toDateString(),
        ]);
        NotificationLog::create([
            'presenter_request_id' => $requestA->id,
            'recipient_role' => UserRole::Verifikator->value,
            'recipient_name' => 'Verifikator',
            'recipient_phone' => '081111111111',
            'message' => 'Log A',
            'provider' => 'kirimi',
            'status' => NotificationStatus::Sent,
            'created_at' => now(),
        ]);
        NotificationLog::create([
            'presenter_request_id' => $requestB->id,
            'recipient_role' => UserRole::Verifikator->value,
            'recipient_name' => 'Verifikator',
            'recipient_phone' => '081111111111',
            'message' => 'Log B',
            'provider' => 'kirimi',
            'status' => NotificationStatus::Sent,
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.notification-logs.index', ['request_code' => '0001']))
            ->assertOk()
            ->assertSee('PR-202607-0001')
            ->assertDontSee('PR-202607-0099');
    }

    public function test_verifikator_cannot_access_notification_log_page(): void
    {
        $user = User::factory()->create(['role' => UserRole::Verifikator]);

        $this->actingAs($user)
            ->get(route('admin.notification-logs.index'))
            ->assertForbidden();
    }
}
