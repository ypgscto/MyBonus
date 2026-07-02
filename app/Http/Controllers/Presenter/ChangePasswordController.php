<?php

namespace App\Http\Controllers\Presenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Presenter\ChangePasswordRequest;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function edit(): View
    {
        return view('presenter.change-password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $user->update([
            'password' => $request->validated('password'),
            'must_change_password' => false,
        ]);

        $this->auditLog->logPresenterPasswordChanged($user);

        return redirect()
            ->route('presenter.dashboard')
            ->with('status', 'Password berhasil diperbarui.');
    }
}
