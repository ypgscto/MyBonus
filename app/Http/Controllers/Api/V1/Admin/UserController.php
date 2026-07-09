<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use PaginatesApiRequests;

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse
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
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            UserResource::collection($users)->response()->getData(true)
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            ...$request->validated(),
            'must_change_password' => false,
        ]);

        $this->auditLog->logUserCreated($user);

        return ApiResponse::success(new UserResource($user), 'User berhasil ditambahkan.', 201);
    }

    public function show(User $user): JsonResponse
    {
        return ApiResponse::success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $oldAttributes = $user->toArray();
        $data = $request->safe()->except(['password', 'password_confirmation']);

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);
        $this->auditLog->logUserUpdated($user->fresh(), $oldAttributes);

        return ApiResponse::success(new UserResource($user->fresh()), 'User berhasil diperbarui.');
    }

    public function toggleStatus(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return ApiResponse::error('Anda tidak dapat menonaktifkan akun sendiri.', 422, code: 'SELF_DEACTIVATE');
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

        return ApiResponse::success(new UserResource($user->fresh()), $message);
    }

    public function resetPassword(User $user): JsonResponse
    {
        $plainPassword = Str::password(10);

        $user->update([
            'password' => Hash::make($plainPassword),
            'must_change_password' => $user->role === UserRole::Presenter,
        ]);

        $this->auditLog->logUserPasswordReset($user);

        return ApiResponse::success([
            'user' => new UserResource($user->fresh()),
            'temporary_password' => $plainPassword,
        ], 'Password user berhasil direset.');
    }
}
