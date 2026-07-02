<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\NotificationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\NotificationLog;
use App\Models\Presenter;
use App\Models\PresenterRequest;
use App\Models\User;
use App\Support\AccountNumberMasker;
use App\Support\WhatsappNumberHelper;

class PresenterRequestNotificationService
{
    public function __construct(
        private readonly KirimiService $kirimi,
        private readonly AuditLogService $auditLog,
    ) {}

    public function notifySubmittedToVerifikator(PresenterRequest $request): NotificationResult
    {
        $request->loadMissing(['presenter', 'pmbPeriod', 'creator']);

        $message = $this->buildSubmittedMessage($request);

        $verifikators = User::query()
            ->where('role', UserRole::Verifikator)
            ->where('status', UserStatus::Aktif)
            ->get();

        $result = new NotificationResult;

        foreach ($verifikators as $verifikator) {
            $result->merge($this->dispatchToUser($request, $verifikator, $message));
        }

        return $result;
    }

    public function notifyRejectedToAdmin(PresenterRequest $request): NotificationResult
    {
        $request->loadMissing(['presenter', 'creator', 'submitter']);

        $admin = $this->resolveAdminUser($request);
        if (! $admin) {
            return new NotificationResult;
        }

        $message = sprintf(
            "Halo Admin PMB,\n\nPengajuan permintaan presenter telah ditolak oleh Verifikator.\n\nKode Permintaan: %s\nPresenter: %s\nAlasan Penolakan: %s\n\nSilakan login ke aplikasi BONUSKU untuk melihat detail.\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->request_code,
            $request->presenter?->name ?? '-',
            $request->rejection_reason ?? '-'
        );

        return $this->dispatchToUser($request, $admin, $message);
    }

    public function notifyApprovedToAdmin(PresenterRequest $request): NotificationResult
    {
        $request->loadMissing(['presenter', 'creator', 'submitter']);

        $admin = $this->resolveAdminUser($request);
        if (! $admin) {
            return new NotificationResult;
        }

        $message = sprintf(
            "Halo Admin PMB,\n\nPengajuan permintaan presenter telah disetujui oleh Verifikator.\n\nKode Permintaan: %s\nPresenter: %s\nTotal Mahasiswa: %d\nTotal Komisi: Rp %s\n\nProses akan dilanjutkan ke pencairan tahap pertama.\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->request_code,
            $request->presenter?->name ?? '-',
            $request->total_students,
            number_format((float) $request->total_commission, 0, ',', '.')
        );

        return $this->dispatchToUser($request, $admin, $message);
    }

    public function notifyTransferredToFinance(PresenterRequest $request): NotificationResult
    {
        $request->loadMissing(['presenter', 'verifikatorTransfer']);

        $transfer = $request->verifikatorTransfer;
        $transferAmount = number_format((float) ($transfer?->transfer_amount ?? $request->total_commission), 0, ',', '.');
        $transferDate = $transfer?->transfer_date?->format('d M Y') ?? now()->format('d M Y');

        $message = sprintf(
            "Halo Bagian Keuangan/Bendahara,\n\nDana komisi presenter telah ditransfer oleh Verifikator dan membutuhkan proses lanjutan ke presenter.\n\nKode Permintaan: %s\nPresenter: %s\nNominal Transfer: Rp %s\nTanggal Transfer: %s\n\nSilakan login ke aplikasi BONUSKU untuk konfirmasi dana diterima dan lanjutkan pencairan ke presenter.\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->request_code,
            $request->presenter?->name ?? '-',
            $transferAmount,
            $transferDate
        );

        $result = new NotificationResult;

        if ($transfer?->finance_user_id) {
            $financeUser = User::query()
                ->whereKey($transfer->finance_user_id)
                ->where('status', UserStatus::Aktif)
                ->first();

            if ($financeUser) {
                return $this->dispatchToUser($request, $financeUser, $message);
            }
        }

        $financeUsers = User::query()
            ->where('role', UserRole::Keuangan)
            ->where('status', UserStatus::Aktif)
            ->get();

        foreach ($financeUsers as $financeUser) {
            $result->merge($this->dispatchToUser($request, $financeUser, $message));
        }

        return $result;
    }

    public function notifyFinanceReceivedToVerifikator(PresenterRequest $request): NotificationResult
    {
        $request->loadMissing(['presenter', 'verifikatorTransfer']);

        $transfer = $request->verifikatorTransfer;
        $transferAmount = number_format((float) ($transfer?->transfer_amount ?? $request->total_commission), 0, ',', '.');
        $message = sprintf(
            "Halo Bapak/Ibu Verifikator,\n\nBagian Keuangan telah mengkonfirmasi dana diterima.\n\nKode Permintaan: %s\nPresenter: %s\nNominal: Rp %s\n\nProses akan dilanjutkan ke pencairan presenter.\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->request_code,
            $request->presenter?->name ?? '-',
            $transferAmount
        );

        $verifikator = null;
        if ($request->transferred_to_finance_by) {
            $verifikator = User::query()
                ->whereKey($request->transferred_to_finance_by)
                ->where('status', UserStatus::Aktif)
                ->first();
        }

        if ($verifikator) {
            return $this->dispatchToUser($request, $verifikator, $message);
        }

        $result = new NotificationResult;
        $verifikators = User::query()
            ->where('role', UserRole::Verifikator)
            ->where('status', UserStatus::Aktif)
            ->get();

        foreach ($verifikators as $user) {
            $result->merge($this->dispatchToUser($request, $user, $message));
        }

        return $result;
    }

    public function notifyTransferredToPresenter(PresenterRequest $request): NotificationResult
    {
        $request->loadMissing(['presenter.user', 'presenterTransfer', 'creator', 'submitter']);

        $transfer = $request->presenterTransfer;
        $transferAmount = number_format((float) ($transfer?->transfer_amount ?? $request->total_commission), 0, ',', '.');
        $transferDate = $transfer?->transfer_date?->format('d M Y') ?? now()->format('d M Y');
        $bankName = $transfer?->destination_bank ?? $request->presenter?->bank_name ?? '-';
        $maskedAccount = AccountNumberMasker::mask($transfer?->destination_account_number ?? $request->presenter?->account_number);

        $presenterMessage = sprintf(
            "Halo %s,\n\nKomisi presenter Anda telah ditransfer oleh Bagian Keuangan.\n\nKode Permintaan: %s\nTotal Mahasiswa: %d\nNominal Transfer: Rp %s\nTanggal Transfer: %s\nBank Tujuan: %s\nRekening Tujuan: %s\n\nSilakan cek rekening Anda. Jika ada kendala, hubungi Admin PMB.\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->presenter?->name ?? 'Presenter',
            $request->request_code,
            $request->total_students,
            $transferAmount,
            $transferDate,
            $bankName,
            $maskedAccount
        );

        $adminMessage = sprintf(
            "Halo Admin PMB,\n\nBagian Keuangan telah mentransfer komisi ke presenter.\n\nKode Permintaan: %s\nPresenter: %s\nNominal Transfer: Rp %s\nStatus: Transfer ke Presenter\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->request_code,
            $request->presenter?->name ?? '-',
            $transferAmount
        );

        $result = new NotificationResult;

        if ($request->presenter) {
            $result->merge($this->dispatchToPresenter($request, $request->presenter, $presenterMessage));
        }

        $admin = $this->resolveAdminUser($request);
        if ($admin) {
            $result->merge($this->dispatchToUser($request, $admin, $adminMessage));
        }

        return $result;
    }

    public function notifyClosed(PresenterRequest $request): NotificationResult
    {
        $request->loadMissing(['presenter.user', 'creator', 'submitter']);

        $adminMessage = sprintf(
            "Halo Admin PMB,\n\nPermintaan presenter telah selesai/closed.\n\nKode Permintaan: %s\nPresenter: %s\nStatus: Closed\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->request_code,
            $request->presenter?->name ?? '-'
        );

        $presenterMessage = sprintf(
            "Halo %s,\n\nProses pencairan komisi Anda telah selesai.\n\nKode Permintaan: %s\nStatus: Closed\n\nTerima kasih telah berkontribusi dalam PMB STIKES Gunung Sari.\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->presenter?->name ?? 'Presenter',
            $request->request_code
        );

        $result = new NotificationResult;

        $admin = $this->resolveAdminUser($request);
        if ($admin) {
            $result->merge($this->dispatchToUser($request, $admin, $adminMessage));
        }

        if ($request->presenter) {
            $result->merge($this->dispatchToPresenter($request, $request->presenter, $presenterMessage));
        }

        return $result;
    }

    private function buildSubmittedMessage(PresenterRequest $request): string
    {
        return sprintf(
            "Halo Bapak/Ibu Verifikator,\n\nAda pengajuan permintaan presenter baru yang membutuhkan verifikasi.\n\nKode Permintaan: %s\nPresenter: %s\nPeriode PMB: %s\nTotal Mahasiswa: %d\nTotal Komisi: Rp %s\n\nSilakan login ke aplikasi BONUSKU untuk melakukan verifikasi.\n\nBONUSKU\nSTIKES Gunung Sari",
            $request->request_code,
            $request->presenter?->name ?? '-',
            trim(($request->pmbPeriod?->academic_year ?? '').' '.($request->pmbPeriod?->wave ?? '')),
            $request->total_students,
            number_format((float) $request->total_commission, 0, ',', '.')
        );
    }

    private function resolveAdminUser(PresenterRequest $request): ?User
    {
        $admin = $request->submitter ?? $request->creator;

        if (! $admin || $admin->status !== UserStatus::Aktif) {
            return null;
        }

        return $admin;
    }

    private function dispatchToUser(PresenterRequest $request, User $user, string $message): NotificationResult
    {
        return $this->dispatchToPhone(
            $request,
            $user->role->value,
            $user->name,
            $user->phone,
            $message
        );
    }

    private function dispatchToPresenter(PresenterRequest $request, Presenter $presenter, string $message): NotificationResult
    {
        $phone = $presenter->phone;
        if ($presenter->user && filled($presenter->user->phone)) {
            $phone = $presenter->user->phone;
        }

        return $this->dispatchToPhone(
            $request,
            'presenter',
            $presenter->name,
            $phone ?? '',
            $message
        );
    }

    private function dispatchToPhone(
        PresenterRequest $request,
        string $recipientRole,
        string $recipientName,
        string $phone,
        string $message,
    ): NotificationResult {
        $normalizedPhone = WhatsappNumberHelper::normalize($phone);

        if ($normalizedPhone === '' || ! WhatsappNumberHelper::isValidIndonesianNumber($phone)) {
            $log = NotificationLog::create([
                'presenter_request_id' => $request->id,
                'recipient_role' => $recipientRole,
                'recipient_name' => $recipientName,
                'recipient_phone' => $normalizedPhone ?: $phone,
                'message' => $message,
                'provider' => 'kirimi',
                'provider_response' => 'Nomor WhatsApp tidak valid atau kosong.',
                'status' => NotificationStatus::Failed,
                'created_at' => now(),
            ]);

            $this->auditLog->logWhatsappNotificationFailed($request, $log);

            return (new NotificationResult)->record(KirimiSendResult::failed('Nomor WhatsApp tidak valid atau kosong.'));
        }

        $log = NotificationLog::create([
            'presenter_request_id' => $request->id,
            'recipient_role' => $recipientRole,
            'recipient_name' => $recipientName,
            'recipient_phone' => $normalizedPhone,
            'message' => $message,
            'provider' => 'kirimi',
            'status' => NotificationStatus::Pending,
            'created_at' => now(),
        ]);

        $sendResult = $this->kirimi->sendMessage($normalizedPhone, $message);

        if ($sendResult->success) {
            $log->update([
                'status' => NotificationStatus::Sent,
                'provider_response' => $sendResult->response,
                'sent_at' => now(),
            ]);
            $this->auditLog->logWhatsappNotificationSent($request, $log);
        } else {
            $log->update([
                'status' => NotificationStatus::Failed,
                'provider_response' => $sendResult->response,
            ]);
            $this->auditLog->logWhatsappNotificationFailed($request, $log);
        }

        return (new NotificationResult)->record($sendResult);
    }
}
