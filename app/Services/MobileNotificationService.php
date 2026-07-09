<?php

namespace App\Services;

use App\Enums\AppNotificationType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\PresenterRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class MobileNotificationService
{
    public function __construct(
        private readonly FcmPushService $fcm,
    ) {}

    public function notifySubmittedToVerifikator(PresenterRequest $request): void
    {
        $request->loadMissing(['presenter', 'pmbPeriod']);

        $users = User::query()
            ->where('role', UserRole::Verifikator)
            ->where('status', UserStatus::Aktif)
            ->get();

        $title = 'Permintaan Baru Menunggu Verifikasi';
        $body = sprintf(
            '%s · %s · %d mahasiswa',
            $request->request_code,
            $request->presenter?->name ?? '-',
            $request->total_students
        );

        $this->dispatchToUsers($users, $request, AppNotificationType::RequestSubmitted, $title, $body, [
            'screen' => 'verifikator_request_detail',
        ]);
    }

    public function notifyRejectedToAdmin(PresenterRequest $request): void
    {
        $admin = $this->resolveAdminUser($request);
        if (! $admin) {
            return;
        }

        $title = 'Permintaan Ditolak Verifikator';
        $body = sprintf(
            '%s · %s · %s',
            $request->request_code,
            $request->presenter?->name ?? '-',
            $request->rejection_reason ?? '-'
        );

        $this->dispatchToUsers(collect([$admin]), $request, AppNotificationType::RequestRejected, $title, $body, [
            'screen' => 'admin_request_detail',
        ]);
    }

    public function notifyApprovedToAdmin(PresenterRequest $request): void
    {
        $admin = $this->resolveAdminUser($request);
        if (! $admin) {
            return;
        }

        $title = 'Permintaan Disetujui Verifikator';
        $body = sprintf(
            '%s · %s · Rp %s',
            $request->request_code,
            $request->presenter?->name ?? '-',
            number_format((float) $request->total_commission, 0, ',', '.')
        );

        $this->dispatchToUsers(collect([$admin]), $request, AppNotificationType::RequestApproved, $title, $body, [
            'screen' => 'admin_request_detail',
        ]);
    }

    public function notifyTransferredToFinance(PresenterRequest $request): void
    {
        $request->loadMissing(['presenter', 'verifikatorTransfer']);
        $transfer = $request->verifikatorTransfer;
        $amount = number_format((float) ($transfer?->transfer_amount ?? $request->total_commission), 0, ',', '.');

        $title = 'Dana Transfer dari Verifikator';
        $body = sprintf(
            '%s · %s · Rp %s',
            $request->request_code,
            $request->presenter?->name ?? '-',
            $amount
        );

        $users = collect();

        if ($transfer?->finance_user_id) {
            $financeUser = User::query()
                ->whereKey($transfer->finance_user_id)
                ->where('status', UserStatus::Aktif)
                ->first();

            if ($financeUser) {
                $users = collect([$financeUser]);
            }
        }

        if ($users->isEmpty()) {
            $users = User::query()
                ->where('role', UserRole::Keuangan)
                ->where('status', UserStatus::Aktif)
                ->get();
        }

        $this->dispatchToUsers($users, $request, AppNotificationType::TransferredToFinance, $title, $body, [
            'screen' => 'keuangan_request_detail',
        ]);
    }

    public function notifyFinanceReceivedToVerifikator(PresenterRequest $request): void
    {
        $request->loadMissing(['presenter', 'verifikatorTransfer']);

        $verifikator = null;
        if ($request->transferred_to_finance_by) {
            $verifikator = User::query()
                ->whereKey($request->transferred_to_finance_by)
                ->where('status', UserStatus::Aktif)
                ->first();
        }

        $users = $verifikator
            ? collect([$verifikator])
            : User::query()
                ->where('role', UserRole::Verifikator)
                ->where('status', UserStatus::Aktif)
                ->get();

        $title = 'Dana Dikonfirmasi Keuangan';
        $body = sprintf(
            '%s · %s · siap transfer ke presenter',
            $request->request_code,
            $request->presenter?->name ?? '-'
        );

        $this->dispatchToUsers($users, $request, AppNotificationType::ReceivedByFinance, $title, $body, [
            'screen' => 'verifikator_request_detail',
        ]);
    }

    public function notifyTransferredToPresenter(PresenterRequest $request): void
    {
        $request->loadMissing(['presenter.user', 'presenterTransfer', 'creator', 'submitter']);
        $transfer = $request->presenterTransfer;
        $amount = number_format((float) ($transfer?->transfer_amount ?? $request->total_commission), 0, ',', '.');

        $presenterTitle = 'Komisi Anda Telah Ditransfer';
        $presenterBody = sprintf(
            '%s · Rp %s · cek rekening Anda',
            $request->request_code,
            $amount
        );

        if ($request->presenter?->user) {
            $this->dispatchToUsers(
                collect([$request->presenter->user]),
                $request,
                AppNotificationType::TransferredToPresenter,
                $presenterTitle,
                $presenterBody,
                ['screen' => 'presenter_payout_detail']
            );
        }

        $admin = $this->resolveAdminUser($request);
        if ($admin) {
            $adminTitle = 'Komisi Ditransfer ke Presenter';
            $adminBody = sprintf(
                '%s · %s · Rp %s',
                $request->request_code,
                $request->presenter?->name ?? '-',
                $amount
            );

            $this->dispatchToUsers(
                collect([$admin]),
                $request,
                AppNotificationType::TransferredToPresenter,
                $adminTitle,
                $adminBody,
                ['screen' => 'admin_request_detail']
            );
        }
    }

    public function notifyClosed(PresenterRequest $request): void
    {
        $request->loadMissing(['presenter.user', 'creator', 'submitter']);

        $title = 'Permintaan Selesai';
        $body = sprintf('%s · %s · Closed', $request->request_code, $request->presenter?->name ?? '-');

        $users = collect();

        $admin = $this->resolveAdminUser($request);
        if ($admin) {
            $users->push($admin);
        }

        if ($request->presenter?->user) {
            $users->push($request->presenter->user);
        }

        $this->dispatchToUsers($users->unique('id'), $request, AppNotificationType::RequestClosed, $title, $body, [
            'screen' => 'request_detail',
        ]);
    }

    /**
     * @param  Collection<int, User>|list<User>  $users
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchToUsers(
        Collection|array $users,
        PresenterRequest $request,
        AppNotificationType $type,
        string $title,
        string $body,
        array $extraData = [],
    ): void {
        $users = $users instanceof Collection ? $users : collect($users);

        foreach ($users as $user) {
            if ($user->status !== UserStatus::Aktif) {
                continue;
            }

            $data = array_merge([
                'type' => $type->value,
                'presenter_request_id' => $request->id,
                'request_code' => $request->request_code,
            ], $extraData);

            AppNotification::create([
                'user_id' => $user->id,
                'presenter_request_id' => $request->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'created_at' => now(),
            ]);

            $tokens = DeviceToken::query()
                ->where('user_id', $user->id)
                ->pluck('token')
                ->all();

            $this->fcm->sendToTokens($tokens, $title, $body, $data);
        }
    }

    private function resolveAdminUser(PresenterRequest $request): ?User
    {
        $request->loadMissing(['submitter', 'creator']);
        $admin = $request->submitter ?? $request->creator;

        if (! $admin || $admin->status !== UserStatus::Aktif) {
            return null;
        }

        return $admin;
    }
}
