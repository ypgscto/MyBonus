<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
    ) {}

    public function superAdmin(): View
    {
        return view('dashboard.super-admin', $this->dashboard->superAdmin());
    }

    public function adminPmb(): View
    {
        return view('dashboard.admin-pmb', $this->dashboard->adminPmb(auth()->user()));
    }
}
