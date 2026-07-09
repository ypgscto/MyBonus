<?php

namespace App\Http\Controllers\Api\V1\Presenter;

use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Http\Resources\PresenterRequestResource;
use App\Models\PresenterRequest;
use App\Services\PresenterPortalService;
use App\Services\PresenterTransferProofService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutController extends Controller
{
    use PaginatesApiRequests;
    use ResolvesAuthenticatedPresenter;

    public function __construct(
        private readonly PresenterPortalService $portalService,
        private readonly PresenterTransferProofService $proofService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $presenter = $this->authenticatedPresenter();
        $filters = $request->only([
            'pmb_period_id', 'status', 'payout_status',
            'date_from', 'date_to', 'transfer_from', 'transfer_to', 'search',
        ]);
        $payouts = $this->portalService->payouts($presenter, $filters, $this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($payouts)->response()->getData(true)
        );
    }

    public function downloadProof(PresenterRequest $presenterRequest): StreamedResponse|JsonResponse
    {
        Gate::authorize('presenter-view-transfer-proof', $presenterRequest);

        $transfer = $presenterRequest->presenterTransfer;

        if (! $transfer?->transfer_proof_file || ! $this->proofService->exists($transfer->transfer_proof_file)) {
            return ApiResponse::error('Bukti transfer tidak ditemukan.', 404, code: 'PROOF_NOT_FOUND');
        }

        return Storage::disk('presenter_transfers')->download($transfer->transfer_proof_file);
    }
}
