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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PresenterRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PmbPeriod $period;

    private Presenter $presenter;

    private CommissionScheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('payment_proofs');

        $this->admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        User::factory()->create(['role' => UserRole::Verifikator, 'phone' => '081111111111']);

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
        $this->scheme = CommissionScheme::create([
            'presenter_category_id' => $category->id,
            'pmb_period_id' => $this->period->id,
            'commission_amount_per_student' => 500000,
            'status' => RecordStatus::Aktif,
        ]);
    }

    public function test_admin_can_create_draft_request(): void
    {
        $response = $this->actingAs($this->admin)->post(route('presenter-requests.store'), [
            'action' => 'draft',
            'pmb_period_id' => $this->period->id,
            'presenter_id' => $this->presenter->id,
            'admin_note' => 'Catatan test',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('presenter_requests', [
            'status' => 'draft',
            'created_by' => $this->admin->id,
            'admin_note' => 'Catatan test',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'draft_created',
            'module' => 'presenter_request',
        ]);
    }

    public function test_admin_can_add_student_detail_to_draft(): void
    {
        $request = $this->createDraftRequest();

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
        ])->assertRedirect(route('presenter-requests.edit', $request));

        $this->assertDatabaseHas('presenter_request_details', [
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
        ]);
    }

    public function test_submit_requires_payment_proof(): void
    {
        $request = $this->createDraftRequest();
        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
        ]);

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $request))
            ->assertRedirect(route('presenter-requests.edit', $request))
            ->assertSessionHasErrors();

        $this->assertEquals(PresenterRequestStatus::Draft, $request->fresh()->status);
    }

    public function test_admin_can_submit_request_to_verifikator(): void
    {
        $request = $this->createDraftRequest();
        $file = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof' => $file,
        ]);

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $request))
            ->assertRedirect(route('presenter-requests.show', $request));

        $request->refresh();
        $this->assertEquals(PresenterRequestStatus::Submitted, $request->status);
        $this->assertNotNull($request->submitted_at);
        $this->assertEquals($this->admin->id, $request->submitted_by);
        $this->assertEquals(1, $request->total_students);
        $this->assertEquals(500000, $request->total_commission);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request_submitted',
            'module' => 'presenter_request',
            'reference_id' => $request->id,
        ]);

        $this->assertTrue(NotificationLog::where('presenter_request_id', $request->id)->exists());
    }

    public function test_submitted_request_cannot_be_edited(): void
    {
        $request = PresenterRequest::create([
            'request_code' => 'PR-202607-0001',
            'pmb_period_id' => $this->period->id,
            'presenter_id' => $this->presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now()->toDateString(),
            'submitted_at' => now(),
            'submitted_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('presenter-requests.edit', $request))
            ->assertForbidden();
    }

    public function test_duplicate_nim_rejected_on_submit(): void
    {
        $other = PresenterRequest::create([
            'request_code' => 'PR-202607-0002',
            'pmb_period_id' => $this->period->id,
            'presenter_id' => $this->presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now()->toDateString(),
            'submitted_at' => now(),
            'submitted_by' => $this->admin->id,
        ]);
        PresenterRequestDetail::create([
            'presenter_request_id' => $other->id,
            'nim' => '2026001',
            'student_name' => 'Existing',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'test.pdf',
        ]);

        $request = $this->createDraftRequest();
        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'bukti.pdf',
        ]);

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $request))
            ->assertRedirect(route('presenter-requests.edit', $request))
            ->assertSessionHas('duplicate_nim_report');
    }

    public function test_rejected_nim_cannot_be_resubmitted(): void
    {
        $other = PresenterRequest::create([
            'request_code' => 'PR-202607-0099',
            'pmb_period_id' => $this->period->id,
            'presenter_id' => $this->presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::RejectedByVerifikator,
            'request_date' => now()->toDateString(),
        ]);
        PresenterRequestDetail::create([
            'presenter_request_id' => $other->id,
            'nim' => '2026099',
            'student_name' => 'Rejected Student',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'old.pdf',
        ]);

        $request = $this->createDraftRequest();
        $file = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');
        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => '2026099',
            'student_name' => 'Mahasiswa Baru',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof' => $file,
        ])->assertSessionHasErrors('nim');

        $this->assertEquals(PresenterRequestStatus::Draft, $request->fresh()->status);
    }

    public function test_duplicate_within_request_blocks_submit(): void
    {
        $request = $this->createDraftRequest();

        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'a.pdf',
        ]);
        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '2026001',
            'student_name' => 'Mahasiswa B',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof_file' => 'b.pdf',
        ]);

        $this->actingAs($this->admin)
            ->post(route('presenter-requests.submit', $request))
            ->assertSessionHas('duplicate_nim_report');
    }

    public function test_adding_duplicate_nim_in_same_request_is_rejected(): void
    {
        $request = $this->createDraftRequest();
        $file = UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => '2026001',
            'student_name' => 'Mahasiswa A',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof' => $file,
        ]);

        $this->actingAs($this->admin)->post(route('presenter-requests.details.store', $request), [
            'nim' => '2026001',
            'student_name' => 'Mahasiswa B',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
            'payment_proof' => $file,
        ])->assertSessionHasErrors('nim');
    }

    private function createDraftRequest(): PresenterRequest
    {
        return PresenterRequest::create([
            'request_code' => 'PR-202607-'.str_pad((string) random_int(10, 99), 4, '0', STR_PAD_LEFT),
            'pmb_period_id' => $this->period->id,
            'presenter_id' => $this->presenter->id,
            'created_by' => $this->admin->id,
            'status' => PresenterRequestStatus::Draft,
            'request_date' => now()->toDateString(),
        ]);
    }
}
