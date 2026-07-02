<?php

namespace App\Services;

final class KirimiSendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $response,
    ) {}

    public static function simulated(): self
    {
        return new self(true, 'simulated:notification_queued');
    }

    public static function failed(string $error): self
    {
        return new self(false, $error);
    }

    public static function sent(string $response): self
    {
        return new self(true, $response);
    }
}
