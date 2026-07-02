<?php

namespace Tests\Feature;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PmbPeriod;
use App\Models\User;
use App\Services\PaymentProofService;
use App\Services\VerifikatorWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_passwords_are_hashed_in_database(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $this->assertNotSame('password123', $user->fresh()->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_login_form_includes_csrf_protection(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
                '_token' => csrf_token(),
            ]);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
            '_token' => csrf_token(),
        ])->assertSessionHasErrors('email');
    }

    public function test_php_upload_is_rejected_by_service(): void
    {
        Storage::fake('payment_proofs');

        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(PaymentProofService::class)->store($file);
    }

    public function test_oversized_upload_is_rejected_by_service(): void
    {
        Storage::fake('payment_proofs');

        $file = UploadedFile::fake()->create('bukti.pdf', 6000, 'application/pdf');

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(PaymentProofService::class)->store($file);
    }

    public function test_valid_upload_is_stored_with_unique_name(): void
    {
        Storage::fake('payment_proofs');

        $file = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        [$filename] = app(PaymentProofService::class)->store($file);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.pdf$/', $filename);
        Storage::disk('payment_proofs')->assertExists($filename);
    }

    public function test_closed_request_cannot_be_updated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $request = $this->createRequestForAdmin($admin, PresenterRequestStatus::Closed);

        $this->actingAs($admin)
            ->put(route('presenter-requests.update', $request), [
                'pmb_period_id' => $request->pmb_period_id,
                'presenter_id' => $request->presenter_id,
                'admin_note' => 'Coba ubah',
            ])
            ->assertForbidden();
    }

    public function test_closed_request_detail_cannot_be_added(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $request = $this->createRequestForAdmin($admin, PresenterRequestStatus::Closed);

        $this->actingAs($admin)
            ->post(route('presenter-requests.details.store', $request), [
                'nim' => '2026999',
                'student_name' => 'Mahasiswa',
                'birth_date' => '2000-01-01',
                'payment_date' => '2026-07-01',
            ])
            ->assertForbidden();
    }

    public function test_cannot_approve_request_that_is_not_submitted(): void
    {
        $verifikator = User::factory()->create(['role' => UserRole::Verifikator]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $request = $this->createRequestForAdmin($admin, PresenterRequestStatus::ApprovedByVerifikator);

        $this->actingAs($verifikator)
            ->post(route('verifikator.requests.approve', $request), [
                'verifikator_note' => 'Coba approve',
            ])
            ->assertForbidden();
    }

    public function test_workflow_service_rejects_invalid_status_transition(): void
    {
        $verifikator = User::factory()->create(['role' => UserRole::Verifikator]);
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $request = $this->createRequestForAdmin($admin, PresenterRequestStatus::Draft);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(VerifikatorWorkflowService::class)->approve($request, $verifikator);
    }

    public function test_admin_cannot_download_other_admins_payment_proof(): void
    {
        Storage::fake('payment_proofs');

        $owner = User::factory()->create(['role' => UserRole::AdminPmb]);
        $other = User::factory()->create(['role' => UserRole::AdminPmb]);
        $request = $this->createRequestForAdmin($owner, PresenterRequestStatus::Submitted);

        $detail = PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
            'student_name' => 'Mahasiswa',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'proof.pdf',
        ]);

        Storage::disk('payment_proofs')->put('proof.pdf', 'content');

        $this->actingAs($other)
            ->get(route('payment-proofs.download', $detail))
            ->assertForbidden();
    }

    public function test_keuangan_cannot_download_payment_proof_before_transfer_to_finance(): void
    {
        Storage::fake('payment_proofs');

        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $keuangan = User::factory()->create(['role' => UserRole::Keuangan]);
        $request = $this->createRequestForAdmin($admin, PresenterRequestStatus::Submitted);

        $detail = PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
            'student_name' => 'Mahasiswa',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'proof.pdf',
        ]);

        Storage::disk('payment_proofs')->put('proof.pdf', 'content');

        $this->actingAs($keuangan)
            ->get(route('payment-proofs.download', $detail))
            ->assertForbidden();
    }

    private function createRequestForAdmin(User $admin, PresenterRequestStatus $status): PresenterRequest
    {
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

        return PresenterRequest::create([
            'request_code' => 'PR-202607-'.random_int(1000, 9999),
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => $status,
            'request_date' => now()->toDateString(),
        ]);
    }
}
