<?php

namespace Tests\Feature;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CommissionScheme;
use App\Models\NotificationLog;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PmbPeriod;
use App\Models\User;
use App\Models\VerifikatorTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifikatorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $verifikator;

    private User $keuangan;

    private PmbPeriod $period;

    private Presenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('payment_proofs');
        Storage::fake('verifikator_transfers');

        $this->admin = User::factory()->create(['role' => UserRole::AdminPmb, 'phone' => '081200000001']);
        $this->verifikator = User::factory()->create(['role' => UserRole::Verifikator, 'phone' => '081200000002']);
        $this->keuangan = User::factory()->keuangan()->create(['phone' => '081200000003']);

        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $this->period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'Gelombang 1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);
        $this->presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter A',
            'phone' => '081234567890',
            'status' => RecordStatus::Aktif,
        ]);
        CommissionScheme::create([
            'presenter_category_id' => $category->id,
            'pmb_period_id' => $this->period->id,
            'commission_amount_per_student' => 500000,
            'status' => RecordStatus::Aktif,
        ]);
    }

    public function test_verifikator_can_view_pending_requests(): void
    {
        $request = $this->createSubmittedRequest(2);

        $this->actingAs($this->verifikator)
            ->get(route('verifikator.requests.pending'))
            ->assertOk()
            ->assertSee($request->request_code);
    }

    public function test_verifikator_can_approve_request_and_lock_commission(): void
    {
        $request = $this->createSubmittedRequest(2);

        $this->actingAs($this->verifikator)
            ->post(route('verifikator.requests.approve', $request), [
                'verifikator_note' => 'Sudah dicek',
            ])
            ->assertRedirect(route('verifikator.requests.to-transfer'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::ApprovedByVerifikator, $request->status);
        $this->assertEquals($this->verifikator->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);
        $this->assertEquals(2, $request->total_students);
        $this->assertEquals(500000, $request->commission_per_student);
        $this->assertEquals(1000000, $request->total_commission);
        $this->assertEquals('Sudah dicek', $request->verifikator_note);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request_approved_by_verifikator',
            'module' => 'presenter_request',
            'reference_id' => $request->id,
        ]);

        $this->assertTrue(
            NotificationLog::where('presenter_request_id', $request->id)
                ->where('recipient_role', UserRole::AdminPmb->value)
                ->exists()
        );
    }

    public function test_verifikator_can_reject_request(): void
    {
        $request = $this->createSubmittedRequest(1);

        $this->actingAs($this->verifikator)
            ->post(route('verifikator.requests.reject', $request), [
                'rejection_reason' => 'Bukti pembayaran tidak valid untuk verifikasi.',
            ])
            ->assertRedirect(route('verifikator.requests.rejected'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::RejectedByVerifikator, $request->status);
        $this->assertEquals($this->verifikator->id, $request->rejected_by);
        $this->assertNotNull($request->rejected_at);
        $this->assertEquals('Bukti pembayaran tidak valid untuk verifikasi.', $request->rejection_reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request_rejected_by_verifikator',
            'module' => 'presenter_request',
            'reference_id' => $request->id,
        ]);

        $this->assertTrue(
            NotificationLog::where('presenter_request_id', $request->id)
                ->where('recipient_role', UserRole::AdminPmb->value)
                ->exists()
        );
    }

    public function test_verifikator_can_transfer_to_finance(): void
    {
        $request = $this->createApprovedRequest(2);
        $proof = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->actingAs($this->verifikator)
            ->post(route('verifikator.requests.transfer', $request), [
                'transfer_date' => '2026-07-15',
                'transfer_amount' => 1000000,
                'finance_user_id' => $this->keuangan->id,
                'transfer_proof' => $proof,
            ])
            ->assertRedirect(route('verifikator.requests.transfer-history'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::TransferredToFinance, $request->status);
        $this->assertEquals($this->verifikator->id, $request->transferred_to_finance_by);
        $this->assertNotNull($request->transferred_to_finance_at);

        $this->assertDatabaseHas('verifikator_transfers', [
            'presenter_request_id' => $request->id,
            'transferred_by' => $this->verifikator->id,
            'finance_user_id' => $this->keuangan->id,
            'transfer_amount' => 1000000,
            'destination_bank' => $this->keuangan->bank_name,
            'destination_account_number' => $this->keuangan->account_number,
            'destination_account_name' => $this->keuangan->account_holder_name,
        ]);

        $transfer = VerifikatorTransfer::where('presenter_request_id', $request->id)->first();
        $this->assertNotNull($transfer?->transfer_proof_file);
        Storage::disk('verifikator_transfers')->assertExists($transfer->transfer_proof_file);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transferred_to_finance',
            'module' => 'presenter_request',
            'reference_id' => $request->id,
        ]);

        $this->assertTrue(
            NotificationLog::where('presenter_request_id', $request->id)
                ->where('recipient_role', UserRole::Keuangan->value)
                ->exists()
        );
    }

    public function test_transfer_requires_note_when_amount_differs(): void
    {
        $request = $this->createApprovedRequest(2);
        $proof = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->actingAs($this->verifikator)
            ->post(route('verifikator.requests.transfer', $request), [
                'transfer_date' => '2026-07-15',
                'transfer_amount' => 900000,
                'finance_user_id' => $this->keuangan->id,
                'transfer_proof' => $proof,
            ])
            ->assertSessionHasErrors('note');

        $this->assertEquals(PresenterRequestStatus::ApprovedByVerifikator, $request->fresh()->status);
    }

    public function test_admin_cannot_access_verifikator_routes(): void
    {
        $this->actingAs($this->admin)
            ->get(route('verifikator.requests.pending'))
            ->assertForbidden();
    }

    private function createSubmittedRequest(int $studentCount): PresenterRequest
    {
        $request = PresenterRequest::create([
            'request_code' => 'PR-202607-'.str_pad((string) random_int(10, 99), 4, '0', STR_PAD_LEFT),
            'pmb_period_id' => $this->period->id,
            'presenter_id' => $this->presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now()->toDateString(),
            'submitted_at' => now(),
            'submitted_by' => $this->admin->id,
            'total_students' => $studentCount,
            'total_commission' => $studentCount * 500000,
        ]);

        for ($i = 1; $i <= $studentCount; $i++) {
            PresenterRequestDetail::create([
                'presenter_request_id' => $request->id,
                'nim' => '202600'.$i,
                'student_name' => "Mahasiswa {$i}",
                'birth_date' => '2000-01-01',
                'payment_date' => '2026-07-01',
                'payment_proof_file' => "proof-{$i}.pdf",
            ]);
        }

        return $request;
    }

    private function createApprovedRequest(int $studentCount): PresenterRequest
    {
        $request = $this->createSubmittedRequest($studentCount);
        $request->update([
            'status' => PresenterRequestStatus::ApprovedByVerifikator,
            'approved_by' => $this->verifikator->id,
            'approved_at' => now(),
            'commission_per_student' => 500000,
            'total_commission' => $studentCount * 500000,
        ]);

        return $request->fresh();
    }
}
