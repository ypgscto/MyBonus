<?php

namespace Tests\Feature;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Models\AuditLog;
use App\Models\CommissionScheme;
use App\Models\NotificationLog;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PmbPeriod;
use App\Models\User;
use Database\Seeders\PresenterCategorySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowScenarioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $verifikator;

    private User $keuangan;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('payment_proofs');
        Storage::fake('verifikator_transfers');
        Storage::fake('presenter_transfers');

        $this->seed([UserSeeder::class, PresenterCategorySeeder::class]);

        $this->admin = User::where('email', 'adminpmb@example.com')->firstOrFail();
        $this->verifikator = User::where('email', 'verifikator@example.com')->firstOrFail();
        $this->keuangan = User::where('email', 'keuangan@example.com')->firstOrFail();
    }

    public function test_full_workflow_from_admin_pmb_to_closed(): void
    {
        $category = $this->createCategory('Kategori Uji Workflow');
        $presenter = $this->createPresenter($category, 'Presenter Uji Workflow');
        $period = $this->createPeriod('Gelombang Uji Workflow');
        $this->createCommissionScheme($category, $period);

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.store'), [
                'action' => 'draft',
                'pmb_period_id' => $period->id,
                'presenter_id' => $presenter->id,
                'admin_note' => 'Catatan awal draft',
            ])
            ->assertRedirect();

        $request = PresenterRequest::where('created_by', $this->admin->id)->latest('id')->firstOrFail();
        $proof = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => '202602001',
            'student_name' => 'Mahasiswa Workflow A',
            'birth_date' => '2000-04-10',
            'payment_date' => '2026-07-01',
            'payment_proof' => $proof,
        ])->assertRedirect(route('presenter-requests.edit', $request));

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => '202602002',
            'student_name' => 'Mahasiswa Workflow B',
            'birth_date' => '2000-05-11',
            'payment_date' => '2026-07-02',
            'payment_proof' => UploadedFile::fake()->create('bukti2.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('presenter-requests.edit', $request));

        $this->assertEquals(2, $request->fresh()->details()->count());

        $this->actingAs($this->admin)
            ->put(route('presenter-requests.update', $request), [
                'pmb_period_id' => $period->id,
                'presenter_id' => $presenter->id,
                'admin_note' => 'Catatan draft sudah diedit',
            ])
            ->assertRedirect(route('presenter-requests.edit', $request));

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $request))
            ->assertRedirect(route('presenter-requests.show', $request));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::Submitted, $request->status);
        $this->assertEquals(2, $request->total_students);
        $this->assertEquals(1000000, (int) $request->total_commission);

        $this->actingAs($this->verifikator)
            ->get(route('verifikator.requests.show', $request))
            ->assertOk()
            ->assertSee($request->request_code);

        $this->actingAs($this->verifikator)
            ->post(route('verifikator.requests.approve', $request), [
                'verifikator_note' => 'Disetujui untuk uji workflow',
            ])
            ->assertRedirect(route('verifikator.requests.to-transfer'));

        $this->assertEquals(PresenterRequestStatus::ApprovedByVerifikator, $request->fresh()->status);

        $transferProof = UploadedFile::fake()->create('transfer-verifikator.pdf', 100, 'application/pdf');

        $this->actingAs($this->verifikator)
            ->post(route('verifikator.requests.transfer', $request), [
                'transfer_date' => '2026-07-12',
                'transfer_amount' => 1000000,
                'finance_user_id' => $this->keuangan->id,
                'transfer_proof' => $transferProof,
            ])
            ->assertRedirect(route('verifikator.requests.transfer-history'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::TransferredToFinance, $request->status);

        $this->actingAs($this->keuangan)
            ->post(route('keuangan.requests.confirm-received', $request))
            ->assertRedirect(route('keuangan.requests.to-transfer'));

        $this->assertEquals(PresenterRequestStatus::ReceivedByFinance, $request->fresh()->status);

        $this->actingAs($this->keuangan)
            ->post(route('keuangan.requests.transfer', $request), [
                'transfer_date' => '2026-07-18',
                'transfer_amount' => 1000000,
                'transfer_proof' => UploadedFile::fake()->create('transfer-presenter.pdf', 100, 'application/pdf'),
                'finance_note' => 'Pencairan uji workflow',
            ])
            ->assertRedirect(route('keuangan.requests.disbursement-history'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::TransferredToPresenter, $request->status);

        $this->actingAs($this->keuangan)
            ->post(route('keuangan.requests.close', $request))
            ->assertRedirect(route('keuangan.requests.closed'));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::Closed, $request->status);
        $this->assertFalse($request->isEditable());

        $this->actingAs($this->admin)
            ->put(route('presenter-requests.update', $request), [
                'pmb_period_id' => $period->id,
                'presenter_id' => $presenter->id,
                'admin_note' => 'Tidak boleh',
            ])
            ->assertForbidden();

        $auditActions = AuditLog::where('module', 'presenter_request')
            ->where('reference_id', $request->id)
            ->pluck('action')
            ->all();

        foreach ([
            'draft_created',
            'draft_updated',
            'request_submitted',
            'request_approved_by_verifikator',
            'transferred_to_finance',
            'received_by_finance',
            'transferred_to_presenter',
            'request_closed',
        ] as $action) {
            $this->assertContains($action, $auditActions, "Audit log missing: {$action}");
        }

        $this->assertTrue(
            NotificationLog::where('presenter_request_id', $request->id)->exists(),
            'Notification log should be recorded during workflow'
        );
    }

    public function test_duplicate_nim_blocks_submit_and_shows_previous_request(): void
    {
        [$period, $presenter] = $this->createMasterPair();

        $first = $this->createDraftWithStudent($period, $presenter, '202601001', 'Mahasiswa Pertama');
        $this->submitRequest($first);

        $second = $this->createEmptyDraft($period, $presenter, 'Draft duplicate NIM');
        PresenterRequestDetail::create([
            'presenter_request_id' => $second->id,
            'nim' => '202601001',
            'student_name' => 'Mahasiswa Kedua',
            'birth_date' => '2000-02-11',
            'payment_date' => '2026-07-06',
            'payment_proof_file' => 'demo-proof.pdf',
        ]);

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $second))
            ->assertRedirect(route('presenter-requests.edit', $second))
            ->assertSessionHas('duplicate_nim_report')
            ->assertSessionHas('duplicate_nim_message');

        $report = session('duplicate_nim_report');
        $this->assertNotEmpty($report);
        $this->assertEquals('202601001', $report[0]['nim']);
        $this->assertEquals($first->request_code, $report[0]['previous_request_code']);

        $this->assertEquals(PresenterRequestStatus::Draft, $second->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'duplicate_nim_validation_failed',
            'module' => 'presenter_request',
            'reference_id' => $second->id,
        ]);
    }

    public function test_duplicate_nim_blocks_when_adding_student_to_draft(): void
    {
        [$period, $presenter] = $this->createMasterPair();

        $first = $this->createDraftWithStudent($period, $presenter, '202601003', 'Mahasiswa Blocking');
        $this->submitRequest($first);

        $second = $this->createEmptyDraft($period, $presenter, 'Draft tambah mahasiswa duplicate');

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.details.store', $second), [
                'nim' => '202601003',
                'student_name' => 'Mahasiswa Duplicate Add',
                'birth_date' => '2000-01-01',
                'payment_date' => '2026-07-01',
                'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('nim');
    }

    public function test_nim_cannot_be_resubmitted_after_rejection(): void
    {
        [$period, $presenter] = $this->createMasterPair();

        $rejected = PresenterRequest::create([
            'request_code' => 'PR-202607-8801',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::RejectedByVerifikator,
            'request_date' => now()->toDateString(),
            'rejected_by' => $this->verifikator->id,
            'rejected_at' => now(),
            'rejection_reason' => 'Demo rejected',
        ]);
        PresenterRequestDetail::create([
            'presenter_request_id' => $rejected->id,
            'nim' => '202601001',
            'student_name' => 'Lama Rejected',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'old.pdf',
        ]);

        $newRequest = $this->createEmptyDraft($period, $presenter, 'Draft baru');

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $newRequest), [
            'nim' => '202601001',
            'student_name' => 'Mahasiswa Baru',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('nim');
    }

    public function test_nim_cannot_be_resubmitted_after_cancelled(): void
    {
        [$period, $presenter] = $this->createMasterPair();

        $cancelled = PresenterRequest::create([
            'request_code' => 'PR-202607-8802',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::Cancelled,
            'request_date' => now()->toDateString(),
        ]);
        PresenterRequestDetail::create([
            'presenter_request_id' => $cancelled->id,
            'nim' => '202601002',
            'student_name' => 'Lama Cancelled',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'old.pdf',
        ]);

        $newRequest = $this->createEmptyDraft($period, $presenter, 'Draft cancelled test');

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $newRequest), [
            'nim' => '202601002',
            'student_name' => 'Mahasiswa Baru Cancelled',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('nim');
    }

    public function test_workflow_demo_seeder_creates_expected_scenarios(): void
    {
        $this->seed(\Database\Seeders\WorkflowDemoSeeder::class);

        $this->assertDatabaseHas('presenter_requests', [
            'request_code' => 'PR-202607-9001',
            'status' => PresenterRequestStatus::Closed->value,
        ]);
        $this->assertDatabaseHas('presenter_requests', [
            'request_code' => 'PR-202607-9002',
            'status' => PresenterRequestStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('presenter_requests', [
            'request_code' => 'PR-202607-9003',
            'status' => PresenterRequestStatus::Submitted->value,
        ]);

        $closed = PresenterRequest::where('request_code', 'PR-202607-9001')->firstOrFail();
        $this->assertFalse($closed->isEditable());
        $this->assertEquals(2, $closed->details()->count());

        $draftDup = PresenterRequest::where('request_code', 'PR-202607-9004')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $draftDup))
            ->assertSessionHas('duplicate_nim_report');
    }

  /**
   * @return array{0: PmbPeriod, 1: Presenter}
   */
    private function createMasterPair(): array
    {
        $category = PresenterCategory::where('name', 'Pegawai')->firstOrFail();
        $period = $this->createPeriod('Gelombang Duplicate NIM');
        $presenter = $this->createPresenter($category, 'Presenter Duplicate NIM');
        $this->createCommissionScheme($category, $period);

        return [$period, $presenter];
    }

    private function createCategory(string $name): PresenterCategory
    {
        $this->actingAs($this->admin)
            ->post(route('master.presenter-categories.store'), [
                'name' => $name,
                'description' => 'Untuk uji workflow',
                'status' => 'aktif',
            ])
            ->assertRedirect();

        return PresenterCategory::where('name', $name)->firstOrFail();
    }

    private function createPresenter(PresenterCategory $category, string $name): Presenter
    {
        $email = strtolower(str_replace(' ', '.', $name)).'@example.com';

        $this->actingAs($this->admin)
            ->post(route('master.presenters.store'), [
                'presenter_category_id' => $category->id,
                'name' => $name,
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder_name' => $name,
                'phone' => '081234567890',
                'email' => $email,
                'status' => 'aktif',
            ])
            ->assertRedirect();

        return Presenter::where('name', $name)->firstOrFail();
    }

    private function createPeriod(string $wave): PmbPeriod
    {
        $this->actingAs($this->admin)
            ->post(route('master.pmb-periods.store'), [
                'academic_year' => '2026/2027',
                'wave' => $wave,
                'start_date' => '2026-07-01',
                'end_date' => '2026-12-31',
                'status' => 'aktif',
            ])
            ->assertRedirect();

        return PmbPeriod::where('wave', $wave)->firstOrFail();
    }

    private function createCommissionScheme(PresenterCategory $category, PmbPeriod $period): CommissionScheme
    {
        $this->actingAs($this->admin)
            ->post(route('master.commission-schemes.store'), [
                'presenter_category_id' => $category->id,
                'pmb_period_id' => $period->id,
                'commission_amount_per_student' => 500000,
                'status' => 'aktif',
            ])
            ->assertRedirect();

        return CommissionScheme::query()
            ->where('presenter_category_id', $category->id)
            ->where('pmb_period_id', $period->id)
            ->firstOrFail();
    }

    private function createEmptyDraft(PmbPeriod $period, Presenter $presenter, string $note): PresenterRequest
    {
        $this->actingAs($this->admin)
            ->post(route('presenter-requests.store'), [
                'action' => 'draft',
                'pmb_period_id' => $period->id,
                'presenter_id' => $presenter->id,
                'admin_note' => $note,
            ])
            ->assertRedirect();

        return PresenterRequest::where('created_by', $this->admin->id)->latest('id')->firstOrFail();
    }

    private function createDraftWithStudent(PmbPeriod $period, Presenter $presenter, string $nim, string $name): PresenterRequest
    {
        $request = $this->createEmptyDraft($period, $presenter, "Draft untuk NIM {$nim}");
        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => $nim,
            'student_name' => $name,
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('presenter-requests.edit', $request));

        return $request->fresh();
    }

    private function submitRequest(PresenterRequest $request): void
    {
        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $request))
            ->assertRedirect(route('presenter-requests.show', $request));

        $this->assertEquals(PresenterRequestStatus::Submitted, $request->fresh()->status);
    }
}
