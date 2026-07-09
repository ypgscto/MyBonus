<?php

namespace App\Http\Controllers\Api\V1\Presenter;

use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Http\Resources\PresenterRequestDetailResource;
use App\Services\PresenterPortalService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
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
        $students = $this->portalService->students($presenter, $filters, $this->perPage($request));

        return ApiResponse::success(
            PresenterRequestDetailResource::collection($students)->response()->getData(true)
        );
    }
}
