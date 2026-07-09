<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Notification\RegisterDeviceTokenRequest;
use App\Http\Resources\AppNotificationResource;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use PaginatesApiRequests;

    public function index(Request $request): JsonResponse
    {
        $query = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->boolean('unread_only'), fn ($q) => $q->whereNull('read_at'))
            ->latest('created_at');

        $notifications = $query->paginate($this->perPage($request));

        return ApiResponse::success(
            AppNotificationResource::collection($notifications)->response()->getData(true)
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return ApiResponse::success(['unread_count' => $count]);
    }

    public function registerDevice(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        $deviceToken = DeviceToken::query()->updateOrCreate(
            ['token' => $request->string('token')->toString()],
            [
                'user_id' => $user->id,
                'platform' => $request->string('platform')->toString(),
                'device_name' => $request->input('device_name'),
                'last_used_at' => now(),
            ]
        );

        return ApiResponse::success([
            'id' => $deviceToken->id,
            'platform' => $deviceToken->platform,
            'device_name' => $deviceToken->device_name,
        ], 'Device token berhasil didaftarkan.');
    }

    public function unregisterDevice(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $request->string('token')->toString())
            ->delete();

        return ApiResponse::success(null, 'Device token berhasil dihapus.');
    }

    public function markAsRead(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        $notification->markAsRead();

        return ApiResponse::success(new AppNotificationResource($notification->fresh()));
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'Semua notifikasi ditandai sudah dibaca.');
    }
}
