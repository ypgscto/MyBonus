<?php

namespace App\Support;

use App\Enums\PresenterRequestStatus;
use App\Models\PresenterRequest;
use Illuminate\Validation\ValidationException;

trait LocksPresenterRequestForWorkflow
{
    protected function lockPresenterRequest(int $requestId): PresenterRequest
    {
        return PresenterRequest::query()
            ->whereKey($requestId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function assertRequestStatus(
        PresenterRequest $request,
        PresenterRequestStatus $expected,
        string $field,
        string $message,
    ): void {
        if ($request->status !== $expected) {
            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }
    }
}
