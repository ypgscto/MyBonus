<?php

namespace Tests\Feature;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PmbPeriod;
use App\Models\User;
use App\Models\VerifikatorTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_reports(): void
    {
        $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Filter Laporan')
            ->assertSee('Jenis Laporan');
    }

    public function test_admin_pmb_can_access_reports(): void
    {
        $user = User::factory()->create(['role' => UserRole::AdminPmb]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_verifikator_cannot_access_reports(): void
    {
        $user = User::factory()->create(['role' => UserRole::Verifikator]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_verifikator_transfers_report_shows_proof_download(): void
    {
        Storage::fake('verifikator_transfers');

        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $verifikator = User::factory()->create(['role' => UserRole::Verifikator]);
        $keuangan = User::factory()->keuangan()->create();
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'G1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);
        $presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1',
            'account_holder_name' => 'A',
            'phone' => '081234567890',
            'status' => RecordStatus::Aktif,
        ]);

        $request = PresenterRequest::create([
            'request_code' => 'PR-202608-0017',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::TransferredToFinance,
            'request_date' => now()->toDateString(),
            'transferred_to_finance_at' => now(),
            'total_students' => 1,
            'total_commission' => 1500000,
        ]);

        VerifikatorTransfer::create([
            'presenter_request_id' => $request->id,
            'transferred_by' => $verifikator->id,
            'transfer_date' => now()->toDateString(),
            'transfer_amount' => 1500000,
            'finance_user_id' => $keuangan->id,
            'destination_bank' => 'Mandiri',
            'destination_account_number' => '123',
            'destination_account_name' => 'Keuangan',
            'transfer_proof_file' => 'verifikator-proof.pdf',
        ]);

        Storage::disk('verifikator_transfers')->put('verifikator-proof.pdf', 'fake-proof');

        $this->actingAs($superAdmin)
            ->get(route('reports.index', ['type' => ReportType::VerifikatorTransfers->value]))
            ->assertOk()
            ->assertSee('PR-202608-0017')
            ->assertSee('Bukti TF')
            ->assertSee('Unduh')
            ->assertSee(route('verifikator-transfer-proofs.download', $request), false);

        $this->actingAs($superAdmin)
            ->get(route('verifikator-transfer-proofs.download', $request))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('verifikator-transfer-proofs.download', $request))
            ->assertOk();
    }

    public function test_transfer_variance_report_shows_selisih_badge(): void
    {
        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $verifikator = User::factory()->create(['role' => UserRole::Verifikator]);
        $keuangan = User::factory()->keuangan()->create();
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'G1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);
        $presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1',
            'account_holder_name' => 'A',
            'phone' => '081',
            'status' => RecordStatus::Aktif,
        ]);

        $request = PresenterRequest::create([
            'request_code' => 'PR-202607-0099',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'status' => PresenterRequestStatus::TransferredToPresenter,
            'request_date' => now()->toDateString(),
            'total_students' => 2,
            'total_commission' => 1000000,
        ]);

        VerifikatorTransfer::create([
            'presenter_request_id' => $request->id,
            'transferred_by' => $verifikator->id,
            'transfer_date' => now()->toDateString(),
            'transfer_amount' => 900000,
            'finance_user_id' => $keuangan->id,
            'destination_bank' => 'Mandiri',
            'destination_account_number' => '123',
            'destination_account_name' => 'Keuangan',
            'transfer_proof_file' => 'proof.pdf',
            'note' => 'Selisih karena pembulatan transfer',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('reports.index', ['type' => ReportType::TransferVariance->value]))
            ->assertOk()
            ->assertSee('PR-202607-0099')
            ->assertSee('Ada Selisih')
            ->assertSee('Selisih karena pembulatan transfer');
    }

    public function test_duplicate_nim_report_lists_repeated_nims(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'G1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);
        $presenter = Presenter::create([
            'presenter_category_id' => $category->id,
            'name' => 'Presenter A',
            'bank_name' => 'BCA',
            'account_number' => '1',
            'account_holder_name' => 'A',
            'phone' => '081',
            'status' => RecordStatus::Aktif,
        ]);

        foreach (['PR-202607-0001', 'PR-202607-0002'] as $code) {
            $req = PresenterRequest::create([
                'request_code' => $code,
                'pmb_period_id' => $period->id,
                'presenter_id' => $presenter->id,
                'created_by' => $admin->id,
                'status' => PresenterRequestStatus::Submitted,
                'request_date' => now()->toDateString(),
            ]);
            PresenterRequestDetail::create([
                'presenter_request_id' => $req->id,
                'nim' => '2026001',
                'student_name' => 'Mahasiswa A',
                'birth_date' => '2000-01-01',
                'payment_date' => '2026-07-01',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('reports.index', ['type' => ReportType::DuplicateNim->value]))
            ->assertOk()
            ->assertSee('2026001')
            ->assertSee('PR-202607-0001')
            ->assertSee('PR-202607-0002');
    }
}
