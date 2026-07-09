<?php

namespace App\Http\Controllers\Api\V1\Presenter;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Http\Resources\PresenterRequestDetailResource;
use App\Http\Resources\PresenterRequestResource;
use App\Http\Resources\PresenterResource;
use App\Http\Resources\PresenterTransferResource;
use App\Services\PresenterPortalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ResolvesAuthenticatedPresenter;

    public function __construct(
        private readonly PresenterPortalService $portalService,
    ) {}

    public function __invoke(): JsonResponse
    {
        $presenter = $this->authenticatedPresenter();
        $data = $this->portalService->dashboard($presenter);

        return ApiResponse::success([
            'presenter' => new PresenterResource($presenter->load('category')),
            'total_students' => $data['totalStudents'],
            'total_requests' => $data['totalRequests'],
            'total_commission' => $data['totalCommission'],
            'paid_commission' => $data['paidCommission'],
            'pending_commission' => $data['pendingCommission'],
            'latest_payout' => $data['latestPayout']
                ? new PresenterTransferResource($data['latestPayout'])
                : null,
            'recent_students' => PresenterRequestDetailResource::collection($data['recentStudents']),
            'recent_requests' => PresenterRequestResource::collection($data['recentRequests']),
        ]);
    }
}
