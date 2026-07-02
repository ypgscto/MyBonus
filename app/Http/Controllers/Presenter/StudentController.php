<?php

namespace App\Http\Controllers\Presenter;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Services\PresenterPortalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ResolvesAuthenticatedPresenter;

    public function __construct(
        private readonly PresenterPortalService $portalService,
    ) {}

    public function index(Request $request): View
    {
        $presenter = $this->authenticatedPresenter();
        $filters = $request->only(['pmb_period_id', 'status', 'search', 'date_from', 'date_to']);
        $students = $this->portalService->students($presenter, $filters);
        $periods = $this->portalService->periodOptions($presenter);

        return view('presenter.students.index', compact('presenter', 'students', 'filters', 'periods'));
    }
}
