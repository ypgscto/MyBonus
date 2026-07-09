<?php

namespace Tests\Feature\Api;

use App\Enums\AppNotificationType;
use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AppNotification;
use App\Models\CommissionScheme;
use App\Models\PmbPeriod;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\User;
use App\Services\MobileNotificationService;
use App\Services\PaymentProofService;
use App\Services\PresenterRequestSubmitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_creates_notification_for_verifikator(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $verifikator = User::factory()->create(['role' => UserRole::Verifikator, 'status' => UserStatus::Aktif]);

        $request = $this->createSubmittedRequest($admin);

        app(PresenterRequestSubmitService::class)->submit($request, $admin->id);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $verifikator->id,
            'presenter_request_id' => $request->id,
            'type' => AppNotificationType::RequestSubmitted->value,
        ]);
    }

    public function test_user_can_register_device_token_and_list_notifications(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Verifikator,
            'status' => UserStatus::Aktif,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/notifications/device-token', [
            'token' => 'fcm-token-abc123',
            'platform' => 'android',
            'device_name' => 'Pixel 8',
        ])->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-abc123',
            'platform' => 'android',
        ]);

        AppNotification::create([
            'user_id' => $user->id,
            'type' => AppNotificationType::RequestSubmitted,
            'title' => 'Test',
            'body' => 'Body',
            'created_at' => now(),
        ]);

        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => UserRole::Keuangan, 'status' => UserStatus::Aktif]);
        Sanctum::actingAs($user);

        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => AppNotificationType::TransferredToFinance,
            'title' => 'Dana Masuk',
            'body' => 'Test body',
            'created_at' => now(),
        ]);

        $this->postJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_transfer_to_presenter_notifies_presenter_and_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $presenterUser = User::factory()->create(['role' => UserRole::Presenter, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'user_id' => $presenterUser->id,
            'name' => 'Presenter Test',
            'bank_name' => 'BCA',
            'account_number' => '123',
            'account_holder_name' => 'Presenter Test',
            'phone' => '081234567890',
            'email' => 'presenter@test.com',
            'status' => RecordStatus::Aktif,
        ]);

        $request = PresenterRequest::create([
            'request_code' => 'REQ-TEST-001',
            'pmb_period_id' => PmbPeriod::create([
                'academic_year' => '2026/2027',
                'wave' => 'Gelombang 1',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(3)->toDateString(),
                'status' => RecordStatus::Aktif,
            ])->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'submitted_by' => $admin->id,
            'status' => PresenterRequestStatus::TransferredToPresenter,
            'request_date' => now()->toDateString(),
            'total_students' => 1,
            'total_commission' => 100000,
        ]);

        app(MobileNotificationService::class)->notifyTransferredToPresenter($request);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $presenterUser->id,
            'type' => AppNotificationType::TransferredToPresenter->value,
        ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'type' => AppNotificationType::TransferredToPresenter->value,
        ]);
    }

    private function createSubmittedRequest(User $admin): PresenterRequest
    {
        Storage::fake('payment_proofs');

        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'Gelombang 1',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'status' => RecordStatus::Aktif,
        ]);
        $presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter A',
            'phone' => '081234567890',
            'email' => 'presentera@test.com',
            'status' => RecordStatus::Aktif,
        ]);

        CommissionScheme::create([
            'presenter_category_id' => $category->id,
            'pmb_period_id' => $period->id,
            'commission_amount_per_student' => 500000,
            'status' => RecordStatus::Aktif,
        ]);

        $request = PresenterRequest::create([
            'request_code' => 'REQ-001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::Draft,
            'request_date' => now()->toDateString(),
        ]);

        $proof = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');
        [$filename] = app(PaymentProofService::class)->store($proof);

        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => 'NIM001',
            'student_name' => 'Mahasiswa Satu',
            'birth_date' => '2000-01-01',
            'payment_date' => now()->toDateString(),
            'payment_proof_file' => $filename,
        ]);

        return $request->fresh();
    }
}
