<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\PresenterRequestResource;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = match ($user->role) {
            UserRole::SuperAdmin => $this->formatSuperAdmin($this->dashboard->superAdmin()),
            UserRole::AdminPmb => $this->formatAdminPmb($this->dashboard->adminPmb($user)),
            UserRole::Verifikator => $this->formatVerifikator($this->dashboard->verifikator()),
            UserRole::Keuangan => $this->formatKeuangan($this->dashboard->keuangan()),
            UserRole::Presenter => ApiResponse::error(
                'Gunakan endpoint /presenter/dashboard untuk role presenter.',
                400,
                code: 'USE_PRESENTER_DASHBOARD'
            ),
        };

        if ($data instanceof JsonResponse) {
            return $data;
        }

        return ApiResponse::success($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formatSuperAdmin(array $data): array
    {
        return [
            'role' => UserRole::SuperAdmin->value,
            'stats' => collect($data)->except(['requestsPerMonth', 'topPresentersByStudents', 'topPresentersByCommission'])->all(),
            'requests_per_month' => $data['requestsPerMonth'],
            'top_presenters_by_students' => $data['topPresentersByStudents'],
            'top_presenters_by_commission' => $data['topPresentersByCommission'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formatAdminPmb(array $data): array
    {
        return [
            'role' => UserRole::AdminPmb->value,
            'counts' => $data['counts'],
            'recent_requests' => PresenterRequestResource::collection($data['recentRequests']),
            'rejected_requests' => PresenterRequestResource::collection($data['rejectedRequests']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formatVerifikator(array $data): array
    {
        return [
            'role' => UserRole::Verifikator->value,
            'counts' => $data['counts'],
            'monthly_transfer_amount' => $data['monthlyTransferAmount'],
            'pending_requests' => PresenterRequestResource::collection($data['pendingRequests']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formatKeuangan(array $data): array
    {
        return [
            'role' => UserRole::Keuangan->value,
            'counts' => $data['counts'],
            'monthly_transfer_amount' => $data['monthlyTransferAmount'],
        ];
    }
}
