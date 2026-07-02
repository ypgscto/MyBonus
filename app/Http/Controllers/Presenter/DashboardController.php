<?php

namespace App\Http\Controllers\Presenter;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Services\PresenterPortalService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesAuthenticatedPresenter;

    public function __construct(
        private readonly PresenterPortalService $portalService,
    ) {}

    public function __invoke(): View
    {
        $presenter = $this->authenticatedPresenter();
        $data = $this->portalService->dashboard($presenter);

        return view('presenter.dashboard', array_merge($data, compact('presenter')));
    }
}
