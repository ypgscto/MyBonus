<?php

namespace App\Services;

class FcmPushResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly int $successCount = 0,
        public readonly int $failureCount = 0,
        public readonly bool $simulated = false,
    ) {}

    public static function sent(int $successCount, int $failureCount, string $message): self
    {
        return new self(
            success: $successCount > 0,
            message: $message,
            successCount: $successCount,
            failureCount: $failureCount,
        );
    }

    public static function simulated(int $tokenCount): self
    {
        return new self(
            success: true,
            message: "Simulated FCM push to {$tokenCount} device(s).",
            successCount: $tokenCount,
            simulated: true,
        );
    }

    public static function skipped(string $message): self
    {
        return new self(success: true, message: $message);
    }

    public static function failed(string $message): self
    {
        return new self(success: false, message: $message);
    }
}
