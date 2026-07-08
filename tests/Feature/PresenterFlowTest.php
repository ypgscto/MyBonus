<?php

namespace Tests\Feature;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Mail\PresenterAccountCreatedMail;
use App\Models\CommissionScheme;
use App\Models\PmbPeriod;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PresenterTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PresenterFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_master_presenter_provisions_user_and_sends_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);

        $this->actingAs($admin)->post(route('master.presenters.store'), [
            'presenter_category_id' => $category->id,
            'name' => 'Presenter Satu',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Satu',
            'phone' => '081234567890',
            'email' => 'presenter1@example.com',
            'status' => 'aktif',
        ])->assertRedirect(route('master.presenters.index'));

        $presenter = Presenter::where('email', 'presenter1@example.com')->firstOrFail();

        $this->assertNotNull($presenter->user_id);
        $this->assertDatabaseHas('users', [
            'email' => 'presenter1@example.com',
            'role' => UserRole::Presenter->value,
            'must_change_password' => true,
        ]);

        Mail::assertSent(PresenterAccountCreatedMail::class, fn ($mail) => $mail->hasTo('presenter1@example.com'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'presenter_account_created',
            'module' => 'presenter',
            'reference_id' => $presenter->id,
        ]);
    }

    public function test_creating_presenter_rejects_email_already_used_by_other_user(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        User::factory()->create([
            'email' => 'hendraamir137@gmail.com',
            'role' => UserRole::Keuangan,
        ]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);

        $this->actingAs($admin)->post(route('master.presenters.store'), [
            'presenter_category_id' => $category->id,
            'name' => 'AMIR',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'AMIR',
            'phone' => '082346649311',
            'email' => 'hendraamir137@gmail.com',
            'status' => 'aktif',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('presenters', [
            'email' => 'hendraamir137@gmail.com',
        ]);
    }

    public function test_creating_presenter_links_orphan_presenter_user_account(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $orphanUser = User::factory()->create([
            'email' => 'orphan.presenter@example.com',
            'role' => UserRole::Presenter,
        ]);

        $this->actingAs($admin)->post(route('master.presenters.store'), [
            'presenter_category_id' => $category->id,
            'name' => 'Presenter Orphan',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Orphan',
            'phone' => '081234567892',
            'email' => 'orphan.presenter@example.com',
            'status' => 'aktif',
        ])->assertRedirect(route('master.presenters.index'));

        $presenter = Presenter::where('email', 'orphan.presenter@example.com')->firstOrFail();

        $this->assertSame($orphanUser->id, $presenter->user_id);
        $this->assertEquals(1, User::where('email', 'orphan.presenter@example.com')->count());
    }

    public function test_presenter_must_change_password_before_dashboard(): void
    {
        $presenterUser = User::factory()->create([
            'role' => UserRole::Presenter,
            'email' => 'presenter.login@example.com',
            'password' => 'TempPass123!',
            'must_change_password' => true,
        ]);

        Presenter::create([
            'user_id' => $presenterUser->id,
            'presenter_category_id' => PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif])->id,
            'name' => 'Presenter Login',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Login',
            'phone' => '081234567891',
            'email' => 'presenter.login@example.com',
            'status' => RecordStatus::Aktif,
            'account_created_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'presenter.login@example.com',
            'password' => 'TempPass123!',
        ])->assertRedirect(route('presenter.change-password'));

        $this->actingAs($presenterUser)
            ->get(route('presenter.dashboard'))
            ->assertRedirect(route('presenter.change-password'));
    }

    public function test_presenter_can_change_password_and_access_dashboard(): void
    {
        $presenterUser = User::factory()->create([
            'role' => UserRole::Presenter,
            'email' => 'presenter.change@example.com',
            'password' => 'TempPass123!',
            'must_change_password' => true,
        ]);

        Presenter::create([
            'user_id' => $presenterUser->id,
            'presenter_category_id' => PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif])->id,
            'name' => 'Presenter Change',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Change',
            'phone' => '081234567892',
            'email' => 'presenter.change@example.com',
            'status' => RecordStatus::Aktif,
        ]);

        $this->actingAs($presenterUser)
            ->post(route('presenter.change-password.update'), [
                'current_password' => 'TempPass123!',
                'password' => 'NewPass123!',
                'password_confirmation' => 'NewPass123!',
            ])
            ->assertRedirect(route('presenter.dashboard'));

        $presenterUser->refresh();
        $this->assertFalse($presenterUser->must_change_password);

        $this->actingAs($presenterUser)
            ->get(route('presenter.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Presenter');
    }

    public function test_presenter_sees_own_students_and_cannot_access_admin_routes(): void
    {
        [$presenterUser, $presenter, $request] = $this->seedPresenterWithSubmittedRequest();
        $otherPresenter = Presenter::create([
            'presenter_category_id' => $presenter->presenter_category_id,
            'name' => 'Presenter Lain',
            'bank_name' => 'BCA',
            'account_number' => '9999999999',
            'account_holder_name' => 'Presenter Lain',
            'phone' => '081299999999',
            'email' => 'other@example.com',
            'status' => RecordStatus::Aktif,
        ]);

        $otherRequest = PresenterRequest::create([
            'request_code' => 'REQ-OTHER-001',
            'pmb_period_id' => $request->pmb_period_id,
            'presenter_id' => $otherPresenter->id,
            'created_by' => User::factory()->create(['role' => UserRole::AdminPmb])->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now(),
            'submitted_at' => now(),
            'total_students' => 1,
            'commission_per_student' => 500000,
            'total_commission' => 500000,
        ]);

        $this->actingAs($presenterUser)
            ->get(route('presenter.students'))
            ->assertOk()
            ->assertSee('Mahasiswa001')
            ->assertDontSee('MahasiswaOther');

        $this->actingAs($presenterUser)
            ->get(route('presenter.requests.show', $otherRequest))
            ->assertForbidden();

        $this->actingAs($presenterUser)
            ->get(route('master.presenters.index'))
            ->assertForbidden();

        $this->actingAs($presenterUser)
            ->get(route('dashboard.super-admin'))
            ->assertForbidden();
    }

    public function test_presenter_sees_transferred_payout_status(): void
    {
        [$presenterUser, $presenter, $request] = $this->seedPresenterWithSubmittedRequest();

        $request->update([
            'status' => PresenterRequestStatus::TransferredToPresenter,
            'transferred_to_presenter_at' => now(),
        ]);

        PresenterTransfer::create([
            'presenter_request_id' => $request->id,
            'transferred_by' => User::factory()->create(['role' => UserRole::Keuangan])->id,
            'transfer_date' => now(),
            'transfer_amount' => 500000,
            'presenter_id' => $presenter->id,
            'destination_bank' => 'BCA',
            'destination_account_number' => '1234567890',
            'destination_account_name' => $presenter->name,
            'transfer_proof_file' => 'proof.pdf',
            'note' => 'Transfer presenter',
        ]);

        $this->actingAs($presenterUser)
            ->get(route('presenter.payouts'))
            ->assertOk()
            ->assertSee('Sudah Ditransfer')
            ->assertSee($request->request_code);
    }

    public function test_presenter_sidebar_menu_is_scoped(): void
    {
        $presenterUser = User::factory()->create([
            'role' => UserRole::Presenter,
            'must_change_password' => false,
        ]);

        Presenter::create([
            'user_id' => $presenterUser->id,
            'presenter_category_id' => PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif])->id,
            'name' => 'Presenter Menu',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Menu',
            'phone' => '081234567893',
            'email' => 'presenter.menu@example.com',
            'status' => RecordStatus::Aktif,
        ]);

        $this->actingAs($presenterUser)
            ->get(route('presenter.dashboard'))
            ->assertSee('Mahasiswa Saya')
            ->assertSee('Permintaan Saya')
            ->assertSee('Status Pencairan')
            ->assertDontSee('Kelola User')
            ->assertDontSee('Audit Log');
    }

    public function test_admin_can_resend_presenter_account_email_via_http(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $presenterUser = User::factory()->create([
            'role' => UserRole::Presenter,
            'email' => 'resend.test@example.com',
        ]);
        $presenter = Presenter::create([
            'user_id' => $presenterUser->id,
            'presenter_category_id' => $category->id,
            'name' => 'Presenter Resend',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Resend',
            'phone' => '081234567896',
            'email' => 'resend.test@example.com',
            'status' => RecordStatus::Aktif,
            'account_created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('master.presenters.resend-account-email', $presenter))
            ->assertRedirect(route('master.presenters.edit', $presenter))
            ->assertSessionHas('status');

        Mail::assertSent(PresenterAccountCreatedMail::class, fn ($mail) => $mail->hasTo('resend.test@example.com'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'presenter_account_email_resent',
            'module' => 'presenter',
            'reference_id' => $presenter->id,
        ]);
    }

    /**
     * @return array{0: User, 1: Presenter, 2: PresenterRequest}
     */
    private function seedPresenterWithSubmittedRequest(): array
    {
        $category = PresenterCategory::create(['name' => 'Pegawai', 'status' => RecordStatus::Aktif]);
        $period = PmbPeriod::create([
            'academic_year' => '2026/2027',
            'wave' => 'Gelombang 1',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => RecordStatus::Aktif,
        ]);

        CommissionScheme::create([
            'presenter_category_id' => $category->id,
            'pmb_period_id' => $period->id,
            'commission_amount_per_student' => 500000,
            'status' => RecordStatus::Aktif,
        ]);

        $presenterUser = User::factory()->create([
            'role' => UserRole::Presenter,
            'email' => 'presenter.data@example.com',
            'must_change_password' => false,
        ]);

        $presenter = Presenter::create([
            'user_id' => $presenterUser->id,
            'presenter_category_id' => $category->id,
            'name' => 'Presenter Data',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder_name' => 'Presenter Data',
            'phone' => '081234567894',
            'email' => 'presenter.data@example.com',
            'status' => RecordStatus::Aktif,
        ]);

        $admin = User::factory()->create(['role' => UserRole::AdminPmb]);

        $request = PresenterRequest::create([
            'request_code' => 'REQ-PRES-001',
            'pmb_period_id' => $period->id,
            'presenter_id' => $presenter->id,
            'created_by' => $admin->id,
            'submitted_by' => $admin->id,
            'status' => PresenterRequestStatus::Submitted,
            'request_date' => now(),
            'submitted_at' => now(),
            'total_students' => 1,
            'commission_per_student' => 500000,
            'total_commission' => 500000,
        ]);

        PresenterRequestDetail::create([
            'presenter_request_id' => $request->id,
            'nim' => 'NIM001',
            'student_name' => 'Mahasiswa001',
            'birth_date' => '2000-01-01',
            'payment_date' => '2026-07-01',
        ]);

        return [$presenterUser, $presenter, $request];
    }
}
