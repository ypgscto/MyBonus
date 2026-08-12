<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Support\WhatsappNumberHelper;
use Illuminate\Validation\ValidationException;

class NotificationResendService
{
    public function __construct(
        private readonly KirimiService $kirimi,
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @return array{success: bool, message: string, log: NotificationLog}
     */
    public function resend(NotificationLog $log): array
    {
        if ($log->status !== NotificationStatus::Failed) {
            throw ValidationException::withMessages([
                'notification_log' => 'Hanya notifikasi berstatus Gagal yang dapat dikirim ulang.',
            ]);
        }

        if (! filled($log->message)) {
            throw ValidationException::withMessages([
                'notification_log' => 'Pesan notifikasi kosong, tidak dapat dikirim ulang.',
            ]);
        }

        $phone = (string) $log->recipient_phone;
        $normalizedPhone = WhatsappNumberHelper::normalize($phone);

        if ($normalizedPhone === '' || ! WhatsappNumberHelper::isValidIndonesianNumber($phone)) {
            throw ValidationException::withMessages([
                'notification_log' => 'Nomor WhatsApp tidak valid atau kosong.',
            ]);
        }

        $newLog = NotificationLog::create([
            'presenter_request_id' => $log->presenter_request_id,
            'recipient_role' => $log->recipient_role,
            'recipient_name' => $log->recipient_name,
            'recipient_phone' => $normalizedPhone,
            'message' => $log->message,
            'provider' => $log->provider ?: 'kirimi',
            'status' => NotificationStatus::Pending,
            'created_at' => now(),
        ]);

        $sendResult = $this->kirimi->sendMessage($normalizedPhone, $log->message);
        $request = $log->presenterRequest;

        if ($sendResult->success) {
            $newLog->update([
                'status' => NotificationStatus::Sent,
                'provider_response' => $sendResult->response,
                'sent_at' => now(),
            ]);

            if ($request) {
                $this->auditLog->logWhatsappNotificationSent($request, $newLog->fresh());
            }

            return [
                'success' => true,
                'message' => 'Notifikasi berhasil dikirim ulang ke '.$normalizedPhone.'.',
                'log' => $newLog->fresh(),
            ];
        }

        $newLog->update([
            'status' => NotificationStatus::Failed,
            'provider_response' => $sendResult->response,
        ]);

        if ($request) {
            $this->auditLog->logWhatsappNotificationFailed($request, $newLog->fresh());
        }

        return [
            'success' => false,
            'message' => 'Kirim ulang gagal: '.($sendResult->response ?: 'Provider menolak pengiriman.'),
            'log' => $newLog->fresh(),
        ];
    }
}
