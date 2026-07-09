<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): FcmPushResult
    {
        $tokens = array_values(array_filter(array_unique($tokens)));

        if ($tokens === []) {
            return FcmPushResult::skipped('Tidak ada device token terdaftar.');
        }

        if (! $this->isConfigured()) {
            if (app()->environment(['local', 'testing'])) {
                return FcmPushResult::simulated(count($tokens));
            }

            return FcmPushResult::failed('Konfigurasi FCM belum lengkap.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key='.config('services.fcm.server_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.fcm.api_url'), [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => collect($data)->map(fn ($value) => (string) $value)->all(),
                'priority' => 'high',
            ]);

            if ($response->failed()) {
                Log::warning('FCM push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return FcmPushResult::failed($response->body() ?: 'FCM mengembalikan respons gagal.');
            }

            $payload = $response->json();
            $successCount = (int) ($payload['success'] ?? 0);
            $failureCount = (int) ($payload['failure'] ?? 0);

            return FcmPushResult::sent($successCount, $failureCount, $response->body() ?: 'ok');
        } catch (\Throwable $e) {
            Log::warning('FCM push exception', ['error' => $e->getMessage()]);

            return FcmPushResult::failed($e->getMessage());
        }
    }

    public function isConfigured(): bool
    {
        return filled(config('services.fcm.server_key'));
    }
}
