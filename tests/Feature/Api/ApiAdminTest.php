<?php

namespace Tests\Feature\Api;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PmbPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pmb_can_list_master_categories(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/master/presenter-categories')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_verifikator_cannot_access_admin_presenter_requests(): void
    {
        $user = User::factory()->create(['role' => UserRole::Verifikator, 'status' => UserStatus::Aktif]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/presenter-requests/drafts')
            ->assertForbidden();
    }

    public function test_super_admin_can_list_users(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin, 'status' => UserStatus::Aktif]);
        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_pmb_cannot_access_user_management(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/users')
            ->assertForbidden();
    }

    public function test_admin_pmb_can_add_student_detail_to_draft_via_api(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
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
            'request_code' => 'REQ-TEST-001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'draft',
            'request_date' => now()->toDateString(),
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/admin/presenter-requests/{$request->id}/details", [
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nim', '2026001');

        $this->assertDatabaseHas('presenter_request_details', [
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
        ]);
    }

    public function test_admin_pmb_can_preview_commission_for_draft_via_api(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Vendor', 'status' => RecordStatus::Aktif]);
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
        \App\Models\CommissionScheme::create([
            'presenter_category_id' => $category->id,
            'pmb_period_id' => $period->id,
            'commission_amount_per_student' => 1500000,
            'status' => RecordStatus::Aktif,
        ]);
        $request = PresenterRequest::create([
            'request_code' => 'REQ-PREVIEW-001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'draft',
            'request_date' => now()->toDateString(),
        ]);
        $request->details()->create([
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/admin/presenter-requests/{$request->id}/commission-preview")
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.total_students', 1)
            ->assertJsonPath('data.commission_per_student', 1500000)
            ->assertJsonPath('data.total_commission', 1500000)
            ->assertJsonPath('data.is_preview', true);
    }

    public function test_admin_pmb_can_update_student_detail_via_api(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Vendor', 'status' => RecordStatus::Aktif]);
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
            'request_code' => 'REQ-UPDATE-001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'draft',
            'request_date' => now()->toDateString(),
        ]);
        $detail = $request->details()->create([
            'nim' => '111111',
            'student_name' => 'Old Name',
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/admin/presenter-requests/{$request->id}/details/{$detail->id}", [
            'nim' => '111111',
            'student_name' => 'Updated Name',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-04',
        ])
            ->assertOk()
            ->assertJsonPath('data.student_name', 'Updated Name');

        $this->assertDatabaseHas('presenter_request_details', [
            'id' => $detail->id,
            'student_name' => 'Updated Name',
        ]);
        $this->assertEquals('2000-01-01', $detail->fresh()->birth_date->toDateString());
    }

    public function test_verifikator_can_fetch_bank_transfer_note_via_api(): void
    {
        $verifikator = User::factory()->create(['role' => UserRole::Verifikator, 'status' => UserStatus::Aktif]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Vendor', 'status' => RecordStatus::Aktif]);
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
            'request_code' => 'PR-202607-9010',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'approved_by_verifikator',
            'request_date' => now()->toDateString(),
        ]);
        $request->details()->createMany([
            ['nim' => '111111', 'student_name' => 'Student A'],
            ['nim' => '7776', 'student_name' => 'Student B'],
        ]);

        Sanctum::actingAs($verifikator);

        $this->getJson("/api/v1/verifikator/requests/{$request->id}/bank-transfer-note")
            ->assertOk()
            ->assertJsonPath('data.bank_transfer_note', 'PR-202607-9010 : 111111, 7776');

        $this->getJson("/api/v1/verifikator/requests/{$request->id}")
            ->assertOk()
            ->assertJsonPath('data.request.bank_transfer_note', 'PR-202607-9010 : 111111, 7776');
    }

    public function test_verifikator_can_download_payment_proof_via_shared_api_route(): void
    {
        \Illuminate\Support\Facades\Storage::fake('payment_proofs');

        $verifikator = User::factory()->create(['role' => UserRole::Verifikator, 'status' => UserStatus::Aktif]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Vendor', 'status' => RecordStatus::Aktif]);
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
            'request_code' => 'PR-202607-9010',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'submitted',
            'request_date' => now()->toDateString(),
        ]);
        $detail = $request->details()->create([
            'nim' => '111111',
            'student_name' => 'Student A',
            'payment_proof_file' => 'proof.pdf',
        ]);

        \Illuminate\Support\Facades\Storage::disk('payment_proofs')->put('proof.pdf', 'proof-content');

        Sanctum::actingAs($verifikator);

        $this->get("/api/v1/presenter-request-details/{$detail->id}/payment-proof")
            ->assertOk();
    }

    public function test_verifikator_can_filter_requests_by_status(): void
    {
        $verifikator = User::factory()->create(['role' => UserRole::Verifikator, 'status' => UserStatus::Aktif]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Vendor', 'status' => RecordStatus::Aktif]);
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

        PresenterRequest::create([
            'request_code' => 'PR-SUBMITTED',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'submitted',
            'request_date' => now()->toDateString(),
        ]);
        PresenterRequest::create([
            'request_code' => 'PR-APPROVED',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'approved_by_verifikator',
            'request_date' => now()->toDateString(),
            'total_students' => 1,
            'commission_per_student' => 500000,
            'total_commission' => 500000,
        ]);
        PresenterRequest::create([
            'request_code' => 'PR-REJECTED',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'rejected_by_verifikator',
            'request_date' => now()->toDateString(),
            'rejection_reason' => 'Data tidak lengkap',
        ]);

        Sanctum::actingAs($verifikator);

        $this->getJson('/api/v1/verifikator/requests?status=submitted')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.request_code', 'PR-SUBMITTED');

        $this->getJson('/api/v1/verifikator/requests?status=approved_by_verifikator')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.request_code', 'PR-APPROVED');

        $this->getJson('/api/v1/verifikator/requests?status=rejected_by_verifikator')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.request_code', 'PR-REJECTED');
    }

    public function test_keuangan_can_download_payment_proof_after_transfer_to_finance(): void
    {
        \Illuminate\Support\Facades\Storage::fake('payment_proofs');

        $keuangan = User::factory()->create(['role' => UserRole::Keuangan, 'status' => UserStatus::Aktif]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Vendor', 'status' => RecordStatus::Aktif]);
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
            'request_code' => 'PR-KEUANGAN-PROOF',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'transferred_to_finance',
            'request_date' => now()->toDateString(),
        ]);
        $detail = $request->details()->create([
            'nim' => '111111',
            'student_name' => 'Student A',
            'payment_proof_file' => 'proof.pdf',
        ]);

        \Illuminate\Support\Facades\Storage::disk('payment_proofs')->put('proof.pdf', 'proof-content');

        Sanctum::actingAs($keuangan);

        $this->get("/api/v1/presenter-request-details/{$detail->id}/payment-proof")
            ->assertOk();
    }

    public function test_verifikator_can_transfer_to_finance_via_api_without_note_when_amount_matches(): void
    {
        \Illuminate\Support\Facades\Storage::fake('verifikator_transfers');

        $verifikator = User::factory()->create(['role' => UserRole::Verifikator, 'status' => UserStatus::Aktif]);
        $keuangan = User::factory()->create([
            'role' => UserRole::Keuangan,
            'status' => UserStatus::Aktif,
            'bank_name' => 'BRI',
            'account_number' => '1929292939399339',
            'account_holder_name' => 'Keuangan',
        ]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb, 'status' => UserStatus::Aktif]);
        $category = PresenterCategory::create(['name' => 'Vendor', 'status' => RecordStatus::Aktif]);
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
            'request_code' => 'PR-API-TRANSFER',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => 'approved_by_verifikator',
            'request_date' => now()->toDateString(),
            'total_students' => 2,
            'commission_per_student' => 1500000,
            'total_commission' => 3000000,
        ]);

        Sanctum::actingAs($verifikator);

        $this->post("/api/v1/verifikator/requests/{$request->id}/transfer", [
            'transfer_date' => '2026-07-09',
            'transfer_amount' => 3000000,
            'finance_user_id' => $keuangan->id,
            'transfer_proof' => \Illuminate\Http\UploadedFile::fake()->image('proof.jpg'),
        ])
            ->assertOk()
            ->assertJsonPath('data.request.status', 'transferred_to_finance');

        $this->assertDatabaseHas('presenter_requests', [
            'id' => $request->id,
            'status' => 'transferred_to_finance',
        ]);
    }
}
