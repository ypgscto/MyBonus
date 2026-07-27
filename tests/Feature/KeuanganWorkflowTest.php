<?php

namespace Tests\Feature;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\CommissionScheme;
use App\Models\NotificationLog;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PresenterTransfer;
use App\Models\PmbPeriod;
use App\Models\User;
use App\Models\VerifikatorTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KeuanganWorkflowTest extends TestCase
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
        Storage::fake('presenter_transfers');

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

    public function test_keuangan_can_view_incoming_requests(): void
    {
        $request = $this->createTransferredToFinanceRequest(2);

        $this->actingAs($this->keuangan)
            ->get(route('keuangan.requests.incoming'))
            ->assertOk()
            ->assertSee($request->request_code);
    }

    public function test_keuangan_can_confirm_received(): void
    {
        $request = $this->createTransferredToFinanceRequest(2);

        $this->actingAs($this->keuangan)
            ->post(route('keuangan.requests.confirm-received', $request))
            ->assertRedirect(route('keuangan.requests.to-transfer'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::ReceivedByFinance, $request->status);
        $this->assertEquals($this->keuangan->id, $request->received_by_finance_by);
        $this->assertNotNull($request->received_by_finance_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'received_by_finance',
            'module' => 'presenter_request',
            'reference_id' => $request->id,
        ]);

        $this->assertTrue(
            NotificationLog::where('presenter_request_id', $request->id)
                ->where('recipient_role', UserRole::Verifikator->value)
                ->exists()
        );
    }

    public function test_keuangan_can_transfer_to_presenter(): void
    {
        $request = $this->createReceivedByFinanceRequest(2);
        $proof = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->actingAs($this->keuangan)
            ->post(route('keuangan.requests.transfer', $request), [
                'transfer_date' => '2026-07-20',
                'transfer_amount' => 1000000,
                'transfer_proof' => $proof,
                'finance_note' => 'Pencairan komisi gelombang 1',
            ])
            ->assertRedirect(route('keuangan.requests.disbursement-history'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::TransferredToPresenter, $request->status);
        $this->assertEquals($this->keuangan->id, $request->transferred_to_presenter_by);
        $this->assertEquals('Pencairan komisi gelombang 1', $request->finance_note);

        $this->assertDatabaseHas('presenter_transfers', [
            'presenter_request_id' => $request->id,
            'presenter_id' => $this->presenter->id,
            'transfer_amount' => 1000000,
            'destination_bank' => $this->presenter->bank_name,
            'destination_account_number' => $this->presenter->account_number,
            'destination_account_name' => $this->presenter->account_holder_name,
        ]);

        $transfer = PresenterTransfer::where('presenter_request_id', $request->id)->first();
        Storage::disk('presenter_transfers')->assertExists($transfer->transfer_proof_file);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transferred_to_presenter',
            'module' => 'presenter_request',
            'reference_id' => $request->id,
        ]);

        $this->assertTrue(
            NotificationLog::where('presenter_request_id', $request->id)
                ->where('recipient_role', 'presenter')
                ->exists()
        );

        $this->assertTrue(
            NotificationLog::where('presenter_request_id', $request->id)
                ->where('recipient_role', UserRole::AdminPmb->value)
                ->exists()
        );
    }

    public function test_verifikator_and_admin_pmb_can_download_presenter_transfer_proof(): void
    {
        $request = $this->createTransferredToPresenterRequest(1);
        Storage::disk('presenter_transfers')->put('presenter-proof.pdf', 'fake-proof');

        $this->actingAs($this->verifikator)
            ->get(route('presenter-transfer-proofs.download', $request))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('presenter-transfer-proofs.download', $request))
            ->assertOk();

        $this->actingAs($this->verifikator)
            ->get(route('verifikator.requests.show', $request))
            ->assertOk()
            ->assertSee('Unduh Bukti Transfer ke Presenter')
            ->assertSee(route('presenter-transfer-proofs.download', $request), false);

        $this->actingAs($this->admin)
            ->get(route('presenter-requests.show', $request))
            ->assertOk()
            ->assertSee('Unduh Bukti Transfer ke Presenter')
            ->assertSee(route('presenter-transfer-proofs.download', $request), false);
    }

    public function test_admin_pmb_cannot_download_presenter_proof_for_other_admin_request(): void
    {
        $otherAdmin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $request = $this->createTransferredToPresenterRequest(1);
        $request->update(['created_by' => $otherAdmin->id]);
        Storage::disk('presenter_transfers')->put('presenter-proof.pdf', 'fake-proof');

        $this->actingAs($this->admin)
            ->get(route('presenter-transfer-proofs.download', $request))
            ->assertForbidden();
    }

    public function test_transfer_requires_note_when_amount_differs(): void
    {
        $request = $this->createReceivedByFinanceRequest(2);
        $proof = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->actingAs($this->keuangan)
            ->post(route('keuangan.requests.transfer', $request), [
                'transfer_date' => '2026-07-20',
                'transfer_amount' => 900000,
                'transfer_proof' => $proof,
            ])
            ->assertSessionHasErrors('note');

        $this->assertEquals(PresenterRequestStatus::ReceivedByFinance, $request->fresh()->status);
    }

    public function test_keuangan_can_close_request(): void
    {
        $request = $this->createTransferredToPresenterRequest(2);

        $this->actingAs($this->keuangan)
            ->post(route('keuangan.requests.close', $request))
            ->assertRedirect(route('keuangan.requests.closed'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::Closed, $request->status);
        $this->assertEquals($this->keuangan->id, $request->closed_by);
        $this->assertNotNull($request->closed_at);
        $this->assertFalse($request->isEditable());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request_closed',
            'module' => 'presenter_request',
            'reference_id' => $request->id,
        ]);
    }

    public function test_verifikator_cannot_access_keuangan_routes(): void
    {
        $this->actingAs($this->verifikator)
            ->get(route('keuangan.requests.incoming'))
            ->assertForbidden();
    }

    private function createTransferredToFinanceRequest(int $studentCount): PresenterRequest
    {
        $request = PresenterRequest::create([
            'request_code' => 'PR-202607-'.str_pad((string) random_int(10, 99), 4, '0', STR_PAD_LEFT),
            'pmb_period_id' => $this->period->id,
            'presenter_id' => $this->presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::TransferredToFinance,
            'request_date' => now()->toDateString(),
            'submitted_at' => now(),
            'submitted_by' => $this->admin->id,
            'approved_by' => $this->verifikator->id,
            'approved_at' => now(),
            'transferred_to_finance_by' => $this->verifikator->id,
            'transferred_to_finance_at' => now(),
            'total_students' => $studentCount,
            'commission_per_student' => 500000,
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

        VerifikatorTransfer::create([
            'presenter_request_id' => $request->id,
            'transferred_by' => $this->verifikator->id,
            'transfer_date' => now()->toDateString(),
            'transfer_amount' => $studentCount * 500000,
            'finance_user_id' => $this->keuangan->id,
            'destination_bank' => $this->keuangan->bank_name,
            'destination_account_number' => $this->keuangan->account_number,
            'destination_account_name' => $this->keuangan->account_holder_name,
            'transfer_proof_file' => 'verifikator-proof.pdf',
        ]);

        return $request;
    }

    private function createReceivedByFinanceRequest(int $studentCount): PresenterRequest
    {
        $request = $this->createTransferredToFinanceRequest($studentCount);
        $request->update([
            'status' => PresenterRequestStatus::ReceivedByFinance,
            'received_by_finance_by' => $this->keuangan->id,
            'received_by_finance_at' => now(),
        ]);

        return $request->fresh();
    }

    private function createTransferredToPresenterRequest(int $studentCount): PresenterRequest
    {
        $request = $this->createReceivedByFinanceRequest($studentCount);
        $request->update([
            'status' => PresenterRequestStatus::TransferredToPresenter,
            'transferred_to_presenter_by' => $this->keuangan->id,
            'transferred_to_presenter_at' => now(),
        ]);

        PresenterTransfer::create([
            'presenter_request_id' => $request->id,
            'transferred_by' => $this->keuangan->id,
            'transfer_date' => now()->toDateString(),
            'transfer_amount' => $studentCount * 500000,
            'presenter_id' => $this->presenter->id,
            'destination_bank' => 'BCA',
            'destination_account_number' => '1234567890',
            'destination_account_name' => 'Presenter A',
            'transfer_proof_file' => 'presenter-proof.pdf',
        ]);

        return $request->fresh();
    }
}
