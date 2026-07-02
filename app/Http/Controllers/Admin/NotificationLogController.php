<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexNotificationLogRequest;
use App\Services\NotificationLogQueryService;
use Illuminate\View\View;

class NotificationLogController extends Controller
{
    public function __construct(
        private readonly NotificationLogQueryService $notificationLogs,
    ) {}

    public function index(IndexNotificationLogRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.notification-logs.index', [
            'logs' => $this->notificationLogs->paginate($filters),
            'filters' => $filters,
            'filterOptions' => $this->notificationLogs->filterOptions(),
        ]);
    }
}
