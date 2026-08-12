<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexNotificationLogRequest;
use App\Models\NotificationLog;
use App\Services\NotificationLogQueryService;
use App\Services\NotificationResendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationLogController extends Controller
{
    public function __construct(
        private readonly NotificationLogQueryService $notificationLogs,
        private readonly NotificationResendService $resendService,
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

    public function resend(NotificationLog $notificationLog): RedirectResponse
    {
        $result = $this->resendService->resend($notificationLog);

        return redirect()
            ->route('admin.notification-logs.index')
            ->with($result['success'] ? 'status' : 'warning', $result['message']);
    }
}
