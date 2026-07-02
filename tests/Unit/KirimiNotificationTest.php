<?php

namespace Tests\Unit;

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
use App\Services\KirimiService;
use App\Services\PresenterRequestNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KirimiNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_api_logs_notification_and_returns_failure_result(): void
    {
        config([
            'services.kirimi.api_url' => 'https://kirimi.test/v1/send-message',
            'services.kirimi.user_code' => 'test-user',
            'services.kirimi.secret' => 'test-secret',
            'services.kirimi.device_id' => 'test-device',
        ]);

        Http::fake([
            'https://kirimi.test/*' => Http::response('provider error', 500),
        ]);

        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'phone' => '081200000001']);
        User::factory()->create(['role' => UserRole::Verifikator, 'phone' => '081200000002']);

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
            'total_students' => 1,
            'total_commission' => 500000,
        ]);

        $service = app(PresenterRequestNotificationService::class);
        $result = $service->notifySubmittedToVerifikator($request);

        $this->assertTrue($result->hasFailures());
        $this->assertSame(0, $result->sent);
        $this->assertSame(1, $result->failed);

        $this->assertDatabaseHas('notification_logs', [
            'presenter_request_id' => $request->id,
            'recipient_role' => UserRole::Verifikator->value,
            'recipient_phone' => '6281200000002',
            'provider' => 'kirimi',
            'status' => NotificationStatus::Failed->value,
        ]);

        $log = NotificationLog::first();
        $this->assertStringContainsString('provider error', $log->provider_response);
    }

    public function test_kirimi_service_simulates_when_not_configured(): void
    {
        config([
            'services.kirimi.api_url' => null,
            'services.kirimi.user_code' => null,
            'services.kirimi.secret' => null,
            'services.kirimi.device_id' => null,
        ]);

        $result = app(KirimiService::class)->sendGenericMessage('081234567890', 'test');

        $this->assertTrue($result->success);
        Http::assertNothingSent();
    }
}
