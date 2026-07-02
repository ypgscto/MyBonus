<?php

namespace App\Http\Controllers\Presenter;

use App\Http\Controllers\Concerns\ResolvesAuthenticatedPresenter;
use App\Http\Controllers\Controller;
use App\Support\AccountNumberMasker;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use ResolvesAuthenticatedPresenter;

    public function __invoke(): View
    {
        $presenter = $this->authenticatedPresenter()->load(['category', 'user']);
        $maskedAccount = AccountNumberMasker::mask($presenter->account_number);

        return view('presenter.profile', compact('presenter', 'maskedAccount'));
    }
}
