<?php

namespace Tests\Unit;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PmbPeriod;
use App\Models\User;
use App\Services\DuplicateNimValidatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateNimValidatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private DuplicateNimValidatorService $service;

    private PresenterRequest $currentRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DuplicateNimValidatorService;

        $user = User::factory()->create();
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
            'name' => 'Budi Santoso',
            'bank_name' => 'BCA',
            'account_number' => '1',
            'account_holder_name' => 'Budi',
            'phone' => '081234567890',
            'status' => RecordStatus::Aktif,
        ]);

        $this->currentRequest = PresenterRequest::create([
            'request_code' => 'PR-202607-0010',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $user->id,
            'status' => PresenterRequestStatus::Draft,
            'request_date' => '2026-07-01',
        ]);
    }

    public function test_validate_within_current_request_detects_duplicates(): void
    {
        $details = collect([
            new PresenterRequestDetail(['nim' => '202601001', 'student_name' => 'Andi']),
            new PresenterRequestDetail(['nim' => '202601001', 'student_name' => 'Andi 2']),
        ]);

        $report = $this->service->validateWithinCurrentRequest($details);

        $this->assertCount(2, $report);
        $this->assertEquals('within_request', $report[0]['issue_type']);
    }

    public function test_any_other_request_status_blocks_nim(): void
    {
        $other = $this->createOtherRequest('PR-202607-0003', PresenterRequestStatus::TransferredToFinance);
        PresenterRequestDetail::create([
            'presenter_request_id' => $other->id,
            'nim' => '202601001',
            'student_name' => 'Andi Saputra',
        ]);

        $report = $this->service->validateAgainstOtherRequests($this->currentRequest->id, ['202601001']);

        $this->assertCount(1, $report);
        $this->assertEquals('external_duplicate', $report[0]['issue_type']);
        $this->assertEquals('PR-202607-0003', $report[0]['previous_request_code']);
    }

    public function test_rejected_request_also_blocks_nim(): void
    {
        $other = $this->createOtherRequest('PR-202607-0004', PresenterRequestStatus::RejectedByVerifikator);
        PresenterRequestDetail::create([
            'presenter_request_id' => $other->id,
            'nim' => '202601002',
            'student_name' => 'Budi',
        ]);

        $report = $this->service->validateAgainstOtherRequests($this->currentRequest->id, ['202601002']);

        $this->assertCount(1, $report);
        $this->assertEquals('external_duplicate', $report[0]['issue_type']);
    }

    public function test_draft_other_request_blocks_nim(): void
    {
        $other = $this->createOtherRequest('PR-202607-0005', PresenterRequestStatus::Draft);
        PresenterRequestDetail::create([
            'presenter_request_id' => $other->id,
            'nim' => '202601003',
            'student_name' => 'Citra',
        ]);

        $report = $this->service->validateAgainstOtherRequests($this->currentRequest->id, ['202601003']);

        $this->assertCount(1, $report);
        $this->assertEquals('external_duplicate', $report[0]['issue_type']);
    }

    public function test_same_request_nims_are_not_external_duplicates(): void
    {
        PresenterRequestDetail::create([
            'presenter_request_id' => $this->currentRequest->id,
            'nim' => '202601004',
            'student_name' => 'Diri sendiri',
        ]);

        $report = $this->service->validateAgainstOtherRequests($this->currentRequest->id, ['202601004']);

        $this->assertEmpty($report);
    }

    public function test_get_duplicate_report_merges_all_issues(): void
    {
        $details = collect([
            new PresenterRequestDetail(['nim' => '202601005', 'student_name' => 'A']),
            new PresenterRequestDetail(['nim' => '202601005', 'student_name' => 'B']),
        ]);

        $report = $this->service->getDuplicateReport($this->currentRequest->id, ['202601005'], $details);

        $this->assertNotEmpty($report);
    }

    private function createOtherRequest(string $code, PresenterRequestStatus $status): PresenterRequest
    {
        return PresenterRequest::create([
            'request_code' => $code,
            'pmb_period_id' => $this->currentRequest->pmb_period_id,
            'presenter_id' => $this->currentRequest->presenter_id,
            'created_by' => $this->currentRequest->created_by,
            'status' => $status,
            'request_date' => '2026-07-02',
            'submitted_at' => $status !== PresenterRequestStatus::Draft ? now() : null,
        ]);
    }
}
