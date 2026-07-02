<?php

namespace App\Services;

final class NotificationResult
{
    public int $sent = 0;

    public int $failed = 0;

    public function merge(self $other): self
    {
        $this->sent += $other->sent;
        $this->failed += $other->failed;

        return $this;
    }

    public function record(KirimiSendResult $result): self
    {
        if ($result->success) {
            $this->sent++;
        } else {
            $this->failed++;
        }

        return $this;
    }

    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }

    public function warningMessage(): string
    {
        return 'Proses berhasil, tetapi notifikasi WhatsApp gagal dikirim.';
    }
}
