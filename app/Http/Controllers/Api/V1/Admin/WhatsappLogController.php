<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexNotificationLogRequest;
use App\Services\NotificationLogQueryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class WhatsappLogController extends Controller
{
    public function __construct(
        private readonly NotificationLogQueryService $notificationLogs,
    ) {}

    public function index(IndexNotificationLogRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return ApiResponse::success([
            'logs' => $this->notificationLogs->paginate($filters)->toArray(),
            'filter_options' => $this->notificationLogs->filterOptions(),
        ]);
    }
}
