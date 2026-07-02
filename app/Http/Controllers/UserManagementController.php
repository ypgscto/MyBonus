<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->role))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->validated(),
            'must_change_password' => false,
        ]);

        $this->auditLog->logUserCreated($user);

        return redirect()
            ->route('users.index')
            ->with('status', 'User berhasil ditambahkan.');
    }

    public function show(User $user): View
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $oldAttributes = $user->toArray();
        $data = $request->safe()->except(['password', 'password_confirmation']);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);

        $this->auditLog->logUserUpdated($user->fresh(), $oldAttributes);

        return redirect()
            ->route('users.index')
            ->with('status', 'User berhasil diperbarui.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $oldAttributes = $user->toArray();
        $newStatus = $user->status === UserStatus::Aktif
            ? UserStatus::Nonaktif
            : UserStatus::Aktif;

        $user->update(['status' => $newStatus]);

        if ($newStatus === UserStatus::Nonaktif) {
            $this->auditLog->logUserDeactivated($user->fresh(), $oldAttributes);
            $message = 'User berhasil dinonaktifkan.';
        } else {
            $this->auditLog->logUserUpdated($user->fresh(), $oldAttributes);
            $message = 'User berhasil diaktifkan.';
        }

        return back()->with('status', $message);
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $plainPassword = Str::password(10);

        $user->update([
            'password' => Hash::make($plainPassword),
            'must_change_password' => $user->role === UserRole::Presenter,
        ]);

        $this->auditLog->logUserPasswordReset($user);

        return back()->with('status', "Password user berhasil direset. Password baru: {$plainPassword}");
    }
}
