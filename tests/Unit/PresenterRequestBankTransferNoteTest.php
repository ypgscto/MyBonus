<?php

namespace Tests\Unit;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\PmbPeriod;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresenterRequestBankTransferNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_transfer_note_formats_request_code_and_nims(): void
    {
        $request = $this->createRequest('PR-202607-0002');

        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '20268471',
            'student_name' => 'Debra',
        ]);
        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '20268472',
            'student_name' => 'Budi',
        ]);

        $request->load('details');

        $this->assertSame(
            'PR-202607-0002 : 20268471, 20268472',
            $request->bankTransferNote()
        );
    }

    public function test_bank_transfer_note_with_single_nim(): void
    {
        $request = $this->createRequest('PR-202607-0001');

        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '20268471',
            'student_name' => 'Debra',
        ]);

        $request->load('details');

        $this->assertSame(
            'PR-202607-0001 : 20268471',
            $request->bankTransferNote()
        );
    }

    public function test_payment_date_returns_first_detail_payment_date(): void
    {
        $request = $this->createRequest('PR-202607-0003');

        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '111111',
            'student_name' => 'Student A',
            'payment_date' => '2026-07-09',
        ]);
        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => '7776',
            'student_name' => 'Student B',
            'payment_date' => '2026-07-09',
        ]);

        $request->load('details');

        $this->assertSame('2026-07-09', $request->paymentDate()?->toDateString());
    }

    private function createRequest(string $requestCode): PresenterRequest
    {
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
            'name' => 'Presenter',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter',
            'phone' => '081234567890',
            'email' => 'presenter@example.com',
            'status' => RecordStatus::Aktif,
        ]);

        return PresenterRequest::create([
            'request_code' => $requestCode,
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now(),
        ]);
    }
}
