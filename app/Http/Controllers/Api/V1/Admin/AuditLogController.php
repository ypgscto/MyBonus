<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditLogRequest;
use App\Services\AuditLogQueryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogs,
    ) {}

    public function index(IndexAuditLogRequest $request): JsonResponse
    {
        $filters = $request->validated();

        return ApiResponse::success([
            'logs' => $this->auditLogs->paginate($filters)->toArray(),
            'filter_options' => $this->auditLogs->filterOptions(),
        ]);
    }
}
