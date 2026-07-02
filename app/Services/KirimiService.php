<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Presenter;
use App\Models\User;
use App\Support\WhatsappNumberHelper;

class KirimiService
{
    public function sendMessage(string $phone, string $message): KirimiSendResult
    {
        return $this->sendGenericMessage($phone, $message);
    }

    public function sendGenericMessage(string $phone, string $message): KirimiSendResult
    {
        $normalizedPhone = WhatsappNumberHelper::normalize($phone);

        if ($normalizedPhone === '' || ! WhatsappNumberHelper::isValidIndonesianNumber($phone)) {
            return KirimiSendResult::failed('Nomor telepon tidak valid.');
        }

        if (! $this->isConfigured()) {
            if (app()->environment(['local', 'testing'])) {
                return KirimiSendResult::simulated();
            }

            return KirimiSendResult::failed('Konfigurasi Kirimi belum lengkap.');
        }

        try {
            $response = \Illuminate\Support\Facades\Http::acceptJson()
                ->post(config('services.kirimi.api_url'), [
                    'user_code' => config('services.kirimi.user_code'),
                    'secret' => config('services.kirimi.secret'),
                    'device_id' => config('services.kirimi.device_id'),
                    'receiver' => $normalizedPhone,
                    'message' => $message,
                ]);

            if ($response->failed()) {
                $error = $response->body() ?: 'Kirimi API mengembalikan respons gagal.';

                \Illuminate\Support\Facades\Log::warning('Kirimi API request failed', [
                    'phone' => $normalizedPhone,
                    'status' => $response->status(),
                    'body' => $error,
                ]);

                return KirimiSendResult::failed($error);
            }

            return KirimiSendResult::sent($response->body() ?: 'ok');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Kirimi API exception', [
                'phone' => $normalizedPhone,
                'error' => $e->getMessage(),
            ]);

            return KirimiSendResult::failed($e->getMessage());
        }
    }

    /**
     * @return list<KirimiSendResult>
     */
    public function sendToUsersByRole(string $role, string $message, ?int $presenterRequestId = null): array
    {
        $results = [];

        $users = User::query()
            ->where('role', $role)
            ->where('status', UserStatus::Aktif)
            ->get();

        foreach ($users as $user) {
            $results[] = $this->sendToUser($user->id, $message, $presenterRequestId);
        }

        return $results;
    }

    public function sendToUser(int $userId, string $message, ?int $presenterRequestId = null): KirimiSendResult
    {
        $user = User::query()
            ->whereKey($userId)
            ->where('status', UserStatus::Aktif)
            ->first();

        if (! $user || ! filled($user->phone)) {
            return KirimiSendResult::failed('User tidak ditemukan atau nomor WhatsApp kosong.');
        }

        return $this->sendMessage($user->phone, $message);
    }

    public function sendToPresenter(int $presenterId, string $message, ?int $presenterRequestId = null): KirimiSendResult
    {
        $presenter = Presenter::query()->with('user')->find($presenterId);

        if (! $presenter) {
            return KirimiSendResult::failed('Presenter tidak ditemukan.');
        }

        $phone = $presenter->phone;
        if ($presenter->user && filled($presenter->user->phone)) {
            $phone = $presenter->user->phone;
        }

        if (! filled($phone)) {
            return KirimiSendResult::failed('Nomor WhatsApp presenter kosong.');
        }

        return $this->sendMessage($phone, $message);
    }

    public function sendToVerifikator(string $phone, string $message): KirimiSendResult
    {
        return $this->sendMessage($phone, $message);
    }

    public function sendToKeuangan(string $phone, string $message): KirimiSendResult
    {
        return $this->sendMessage($phone, $message);
    }

    public function sendToAdmin(string $phone, string $message): KirimiSendResult
    {
        return $this->sendMessage($phone, $message);
    }

    public function sendToPresenterPhone(string $phone, string $message): KirimiSendResult
    {
        return $this->sendMessage($phone, $message);
    }

    public function isConfigured(): bool
    {
        return filled(config('services.kirimi.api_url'))
            && filled(config('services.kirimi.user_code'))
            && filled(config('services.kirimi.secret'))
            && filled(config('services.kirimi.device_id'));
    }
}
