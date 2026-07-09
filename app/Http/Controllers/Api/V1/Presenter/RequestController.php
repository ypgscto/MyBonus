<?php

namespace App\Http\Controllers\Api\V1\Presenter;

use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Http\Resources\PresenterRequestResource;
use App\Models\PresenterRequest;
use App\Services\PresenterPortalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    use PaginatesApiRequests;
    use ResolvesAuthenticatedPresenter;

    public function __construct(
        private readonly PresenterPortalService $portalService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $presenter = $this->authenticatedPresenter();
        $filters = $request->only(['pmb_period_id', 'status', 'date_from', 'date_to', 'search']);
        $requests = $this->portalService->requests($presenter, $filters, $this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function show(PresenterRequest $presenterRequest): JsonResponse
    {
        $presenter = $this->authenticatedPresenter();
        $this->authorize('view', $presenterRequest);

        $request = $this->portalService->findOwnedRequest($presenter, $presenterRequest->id);

        return ApiResponse::success(new PresenterRequestResource($request));
    }
}
