<?php

namespace App\Services;

use App\Models\PresenterRequest;

final class WorkflowResult
{
    public function __construct(
        public readonly PresenterRequest $request,
        public readonly NotificationResult $notifications = new NotificationResult,
    ) {}
}
