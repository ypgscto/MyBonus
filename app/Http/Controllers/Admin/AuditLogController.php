<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexAuditLogRequest;
use App\Services\AuditLogQueryService;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogs,
    ) {}

    public function index(IndexAuditLogRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.audit-logs.index', [
            'logs' => $this->auditLogs->paginate($filters),
            'filters' => $filters,
            'filterOptions' => $this->auditLogs->filterOptions(),
        ]);
    }
}
