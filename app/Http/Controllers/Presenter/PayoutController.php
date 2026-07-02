<?php

namespace App\Http\Controllers\Presenter;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Models\PresenterRequest;
use App\Services\PresenterPortalService;
use App\Services\PresenterTransferProofService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutController extends Controller
{
    use ResolvesAuthenticatedPresenter;

    public function __construct(
        private readonly PresenterPortalService $portalService,
        private readonly PresenterTransferProofService $proofService,
    ) {}

    public function index(Request $request): View
    {
        $presenter = $this->authenticatedPresenter();
        $filters = $request->only(['pmb_period_id', 'status', 'payout_status', 'date_from', 'date_to', 'transfer_from', 'transfer_to', 'search']);
        $payouts = $this->portalService->payouts($presenter, $filters);
        $periods = $this->portalService->periodOptions($presenter);

        return view('presenter.payouts.index', compact('presenter', 'payouts', 'filters', 'periods'));
    }

    public function downloadProof(PresenterRequest $presenterRequest): StreamedResponse
    {
        Gate::authorize('presenter-view-transfer-proof', $presenterRequest);

        $transfer = $presenterRequest->presenterTransfer;

        abort_unless($transfer?->transfer_proof_file, 404);
        abort_if(! $this->proofService->exists($transfer->transfer_proof_file), 404);

        return Storage::disk('presenter_transfers')->download($transfer->transfer_proof_file);
    }
}
