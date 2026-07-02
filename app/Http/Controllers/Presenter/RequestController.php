<?php

namespace App\Http\Controllers\Presenter;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Models\PresenterRequest;
use App\Services\PresenterPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestController extends Controller
{
    use ResolvesAuthenticatedPresenter;

    public function __construct(
        private readonly PresenterPortalService $portalService,
    ) {}

    public function index(Request $request): View
    {
        $presenter = $this->authenticatedPresenter();
        $filters = $request->only(['pmb_period_id', 'status', 'date_from', 'date_to', 'search']);
        $requests = $this->portalService->requests($presenter, $filters);
        $periods = $this->portalService->periodOptions($presenter);

        return view('presenter.requests.index', compact('presenter', 'requests', 'filters', 'periods'));
    }

    public function show(PresenterRequest $presenter_request): View
    {
        $presenter = $this->authenticatedPresenter();
        $this->authorize('view', $presenter_request);

        $request = $this->portalService->findOwnedRequest($presenter, $presenter_request->id);

        return view('presenter.requests.show', ['presenter' => $presenter, 'request' => $request]);
    }
}
