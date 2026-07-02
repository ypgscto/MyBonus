<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\PresenterAccountCreatedMail;
use App\Models\Presenter;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PresenterAccountService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function provisionAccount(Presenter $presenter): string
    {
        if ($presenter->user_id) {
            return '';
        }

        $plainPassword = $this->generateTemporaryPassword();

        $user = User::create([
            'name' => $presenter->name,
            'email' => $presenter->email,
            'phone' => $presenter->phone,
            'password' => $plainPassword,
            'role' => UserRole::Presenter,
            'status' => UserStatus::Aktif,
            'must_change_password' => true,
        ]);

        $presenter->update([
            'user_id' => $user->id,
            'account_created_at' => now(),
        ]);

        $this->auditLog->logPresenterAccountCreated($presenter->fresh(), $user);

        return $plainPassword;
    }

    public function generateTemporaryPassword(): string
    {
        $upper = Str::upper(Str::random(2));
        $lower = Str::lower(Str::random(4));
        $digits = (string) random_int(100, 999);
        $symbol = collect(['!', '@', '#', '$'])->random();

        return str_shuffle($upper.$lower.$digits.$symbol.Str::random(2));
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function sendAccountEmail(Presenter $presenter, string $plainPassword): array
    {
        try {
            Mail::to($presenter->email)->send(new PresenterAccountCreatedMail($presenter, $plainPassword));
            $this->auditLog->logPresenterAccountEmailSent($presenter);

            return ['success' => true];
        } catch (\Throwable $exception) {
            Log::error('Gagal mengirim email akun presenter', [
                'presenter_id' => $presenter->id,
                'email' => $presenter->email,
                'error' => $exception->getMessage(),
            ]);

            $this->auditLog->logPresenterAccountEmailFailed($presenter, $exception->getMessage());

            return [
                'success' => false,
                'message' => 'Akun berhasil dibuat tetapi email gagal dikirim. Gunakan tombol Kirim Ulang Email Akun.',
            ];
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function resendAccountEmail(Presenter $presenter): array
    {
        $presenter->load('user');

        if (! $presenter->email) {
            return [
                'success' => false,
                'message' => 'Email presenter wajib diisi sebelum mengirim akun login.',
            ];
        }

        if (! $presenter->user_id || ! $presenter->user) {
            $plainPassword = $this->provisionAccount($presenter->fresh());

            if ($plainPassword === '') {
                return [
                    'success' => false,
                    'message' => 'Gagal membuat akun login presenter.',
                ];
            }

            $result = $this->sendAccountEmail($presenter->fresh(), $plainPassword);

            if ($result['success']) {
                $this->auditLog->logPresenterAccountEmailResent($presenter->fresh());
            }

            return [
                'success' => $result['success'],
                'message' => $result['success']
                    ? 'Akun login berhasil dibuat dan email dikirim ke presenter.'
                    : ($result['message'] ?? 'Akun dibuat tetapi email gagal dikirim.'),
            ];
        }

        $plainPassword = $this->generateTemporaryPassword();

        $presenter->user->update([
            'password' => $plainPassword,
            'must_change_password' => true,
        ]);

        $result = $this->sendAccountEmail($presenter, $plainPassword);

        if ($result['success']) {
            $this->auditLog->logPresenterAccountEmailResent($presenter);

            return [
                'success' => true,
                'message' => 'Email akun presenter berhasil dikirim ulang ke '.$presenter->email.'.',
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Email gagal dikirim.',
        ];
    }
}
