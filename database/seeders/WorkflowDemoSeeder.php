<?php

namespace Database\Seeders;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\CommissionScheme;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PresenterTransfer;
use App\Models\PmbPeriod;
use App\Models\User;
use App\Models\VerifikatorTransfer;
use Database\Seeders\Support\DemoStorageHelper;
use Illuminate\Database\Seeder;

class WorkflowDemoSeeder extends Seeder
{
    private User $admin;

    private User $verifikator;

    private User $keuangan;

    private PresenterCategory $category;

    private Presenter $presenter;

    private PmbPeriod $period;

    private CommissionScheme $scheme;

    public function run(): void
    {
        DemoStorageHelper::ensureUploadDirs();

        $this->admin = User::where('email', 'adminpmb@example.com')->firstOrFail();
        $this->verifikator = User::where('email', 'verifikator@example.com')->firstOrFail();
        $this->keuangan = User::where('email', 'keuangan@example.com')->firstOrFail();

        $this->seedMasterData();
        $this->seedClosedWorkflowDemo();
        $this->seedDraftForManualTesting();
        $this->seedDuplicateNimScenarios();

        $this->command?->newLine();
        $this->command?->info('=== Data Dummy Workflow BONUSKU ===');
        $this->command?->line('Login: adminpmb@example.com / verifikator@example.com / keuangan@example.com');
        $this->command?->line('Password semua user: password123');
        $this->command?->newLine();
        $this->command?->table(
            ['Kode Permintaan', 'Status', 'Kegunaan Uji'],
            [
                ['PR-202607-9001', 'closed', 'Workflow selesai — verifikasi tidak bisa diedit'],
                ['PR-202607-9002', 'draft', '2 mahasiswa + bukti bayar — lanjut submit manual'],
                ['PR-202607-9003', 'submitted', 'NIM 202601001 — blokir duplicate pada PR-202607-9004'],
                ['PR-202607-9004', 'draft', 'NIM 202601001 — coba submit (harus ditolak)'],
        ['PR-202607-9005', 'rejected_by_verifikator', 'NIM 202601001 — tetap memblokir duplicate'],
        ['PR-202607-9006', 'cancelled', 'NIM 202601002 — tetap memblokir duplicate'],
            ]
        );
        $this->command?->line('Master demo: Kategori "Demo Workflow", Presenter "Presenter Demo Workflow"');
        $this->command?->line('Jalankan otomatis: php artisan test --filter=WorkflowScenarioTest');
    }

    private function seedMasterData(): void
    {
        $this->category = PresenterCategory::updateOrCreate(
            ['name' => 'Demo Workflow'],
            ['description' => 'Kategori untuk pengujian workflow end-to-end', 'status' => RecordStatus::Aktif]
        );

        $this->presenter = Presenter::updateOrCreate(
            ['name' => 'Presenter Demo Workflow'],
            [
                'presenter_category_id' => $this->category->id,
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_holder_name' => 'Presenter Demo Workflow',
                'phone' => '081299988877',
                'status' => RecordStatus::Aktif,
            ]
        );

        $this->period = PmbPeriod::updateOrCreate(
            ['academic_year' => '2026/2027', 'wave' => 'Gelombang Demo'],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2026-12-31',
                'status' => RecordStatus::Aktif,
            ]
        );

        $this->scheme = CommissionScheme::updateOrCreate(
            [
                'presenter_category_id' => $this->category->id,
                'pmb_period_id' => $this->period->id,
            ],
            [
                'commission_amount_per_student' => 500000,
                'status' => RecordStatus::Aktif,
            ]
        );
    }

    private function seedClosedWorkflowDemo(): void
    {
        $request = PresenterRequest::updateOrCreate(
            ['request_code' => 'PR-202607-9001'],
            [
                'pmb_period_id' => $this->period->id,
                'presenter_id' => $this->presenter->id,
                'created_by' => $this->admin->id,
                'submitted_by' => $this->admin->id,
                'approved_by' => $this->verifikator->id,
                'transferred_to_finance_by' => $this->verifikator->id,
                'received_by_finance_by' => $this->keuangan->id,
                'transferred_to_presenter_by' => $this->keuangan->id,
                'closed_by' => $this->keuangan->id,
                'status' => PresenterRequestStatus::Closed,
                'request_date' => '2026-07-01',
                'submitted_at' => now()->subDays(10),
                'approved_at' => now()->subDays(9),
                'transferred_to_finance_at' => now()->subDays(8),
                'received_by_finance_at' => now()->subDays(7),
                'transferred_to_presenter_at' => now()->subDays(6),
                'closed_at' => now()->subDays(5),
                'admin_note' => 'Demo workflow — permintaan sudah closed',
                'verifikator_note' => 'Disetujui untuk demo',
                'finance_note' => 'Pencairan demo selesai',
                'total_students' => 2,
                'commission_per_student' => 500000,
                'total_commission' => 1000000,
            ]
        );

        $request->details()->delete();

        foreach ([
            ['nim' => '202601101', 'name' => 'Mahasiswa Demo A'],
            ['nim' => '202601102', 'name' => 'Mahasiswa Demo B'],
        ] as $student) {
            PresenterRequestDetail::create([
                'presenter_request_id' => $request->id,
                'nim' => $student['nim'],
                'student_name' => $student['name'],
                'birth_date' => '2000-05-15',
                'payment_date' => '2026-07-01',
                'payment_proof_file' => DemoStorageHelper::putPdf('payment_proofs'),
            ]);
        }

        $verifikatorProof = DemoStorageHelper::putPdf('verifikator_transfers');
        VerifikatorTransfer::updateOrCreate(
            ['presenter_request_id' => $request->id],
            [
                'transferred_by' => $this->verifikator->id,
                'transfer_date' => '2026-07-10',
                'transfer_amount' => 1000000,
                'finance_user_id' => $this->keuangan->id,
                'destination_bank' => 'Mandiri',
                'destination_account_number' => '9876543210',
                'destination_account_name' => 'Bagian Keuangan',
                'transfer_proof_file' => $verifikatorProof,
                'note' => 'Transfer demo verifikator ke keuangan',
            ]
        );

        $presenterProof = DemoStorageHelper::putPdf('presenter_transfers');
        PresenterTransfer::updateOrCreate(
            ['presenter_request_id' => $request->id],
            [
                'transferred_by' => $this->keuangan->id,
                'transfer_date' => '2026-07-15',
                'transfer_amount' => 1000000,
                'presenter_id' => $this->presenter->id,
                'destination_bank' => 'BCA',
                'destination_account_number' => '1234567890',
                'destination_account_name' => 'Presenter Demo Workflow',
                'transfer_proof_file' => $presenterProof,
                'note' => 'Transfer demo ke presenter',
            ]
        );
    }

    private function seedDraftForManualTesting(): void
    {
        $request = PresenterRequest::updateOrCreate(
            ['request_code' => 'PR-202607-9002'],
            [
                'pmb_period_id' => $this->period->id,
                'presenter_id' => $this->presenter->id,
                'created_by' => $this->admin->id,
                'status' => PresenterRequestStatus::Draft,
                'request_date' => now()->toDateString(),
                'admin_note' => 'Draft demo — siap submit ke verifikator',
            ]
        );

        $request->details()->delete();

        foreach ([
            ['nim' => '202601201', 'name' => 'Calon Mahasiswa Satu'],
            ['nim' => '202601202', 'name' => 'Calon Mahasiswa Dua'],
        ] as $student) {
            PresenterRequestDetail::create([
                'presenter_request_id' => $request->id,
                'nim' => $student['nim'],
                'student_name' => $student['name'],
                'birth_date' => '2001-03-20',
                'payment_date' => '2026-07-05',
                'payment_proof_file' => DemoStorageHelper::putPdf('payment_proofs'),
            ]);
        }
    }

    private function seedDuplicateNimScenarios(): void
    {
        $submitted = PresenterRequest::updateOrCreate(
            ['request_code' => 'PR-202607-9003'],
            [
                'pmb_period_id' => $this->period->id,
                'presenter_id' => $this->presenter->id,
                'created_by' => $this->admin->id,
                'submitted_by' => $this->admin->id,
                'status' => PresenterRequestStatus::Submitted,
                'request_date' => '2026-07-02',
                'submitted_at' => now()->subDay(),
                'total_students' => 1,
                'commission_per_student' => 500000,
                'total_commission' => 500000,
                'admin_note' => 'Permintaan pertama — NIM 202601001 (blocking)',
            ]
        );
        $submitted->details()->delete();
        PresenterRequestDetail::create([
            'presenter_request_id' => $submitted->id,
            'nim' => '202601001',
            'student_name' => 'Mahasiswa NIM Blocking',
            'birth_date' => '2000-01-10',
            'payment_date' => '2026-07-02',
            'payment_proof_file' => DemoStorageHelper::putPdf('payment_proofs'),
        ]);

        $draftDup = PresenterRequest::updateOrCreate(
            ['request_code' => 'PR-202607-9004'],
            [
                'pmb_period_id' => $this->period->id,
                'presenter_id' => $this->presenter->id,
                'created_by' => $this->admin->id,
                'status' => PresenterRequestStatus::Draft,
                'request_date' => now()->toDateString(),
                'admin_note' => 'Coba submit — harus ditolak karena NIM 202601001',
            ]
        );
        $draftDup->details()->delete();
        PresenterRequestDetail::create([
            'presenter_request_id' => $draftDup->id,
            'nim' => '202601001',
            'student_name' => 'Mahasiswa Duplicate Test',
            'birth_date' => '2000-02-11',
            'payment_date' => '2026-07-06',
            'payment_proof_file' => DemoStorageHelper::putPdf('payment_proofs'),
        ]);

        $rejected = PresenterRequest::updateOrCreate(
            ['request_code' => 'PR-202607-9005'],
            [
                'pmb_period_id' => $this->period->id,
                'presenter_id' => $this->presenter->id,
                'created_by' => $this->admin->id,
                'rejected_by' => $this->verifikator->id,
                'status' => PresenterRequestStatus::RejectedByVerifikator,
                'request_date' => '2026-06-15',
                'rejected_at' => now()->subDays(3),
                'rejection_reason' => 'Demo — NIM boleh diajukan ulang',
                'admin_note' => 'Rejected — NIM 202601001 memblokir duplicate',
            ]
        );
        $rejected->details()->delete();
        PresenterRequestDetail::create([
            'presenter_request_id' => $rejected->id,
            'nim' => '202601001',
            'student_name' => 'Mahasiswa Rejected Reapply',
            'birth_date' => '1999-12-01',
            'payment_date' => '2026-06-15',
            'payment_proof_file' => DemoStorageHelper::putPdf('payment_proofs'),
        ]);

        $cancelled = PresenterRequest::updateOrCreate(
            ['request_code' => 'PR-202607-9006'],
            [
                'pmb_period_id' => $this->period->id,
                'presenter_id' => $this->presenter->id,
                'created_by' => $this->admin->id,
                'status' => PresenterRequestStatus::Cancelled,
                'request_date' => '2026-06-01',
                'admin_note' => 'Cancelled — NIM 202601002 memblokir duplicate',
            ]
        );
        $cancelled->details()->delete();
        PresenterRequestDetail::create([
            'presenter_request_id' => $cancelled->id,
            'nim' => '202601002',
            'student_name' => 'Mahasiswa Cancelled Reapply',
            'birth_date' => '2000-08-22',
            'payment_date' => '2026-06-01',
            'payment_proof_file' => DemoStorageHelper::putPdf('payment_proofs'),
        ]);
    }
}
