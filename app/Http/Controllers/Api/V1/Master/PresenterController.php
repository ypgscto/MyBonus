<?php

namespace App\Http\Controllers\Api\V1\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePresenterRequest;
use App\Http\Requests\Master\UpdatePresenterRequest;
use App\Http\Resources\PresenterResource;
use App\Models\Presenter;
use App\Services\AuditLogService;
use App\Services\PresenterAccountService;
use App\Support\ApiResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresenterController extends Controller
{
    use PaginatesApiRequests;

    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly PresenterAccountService $presenterAccountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $presenters = Presenter::query()
            ->with(['category', 'user'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            PresenterResource::collection($presenters)->response()->getData(true)
        );
    }

    public function store(StorePresenterRequest $request): JsonResponse
    {
        $plainPassword = null;

        try {
            $presenter = DB::transaction(function () use ($request, &$plainPassword) {
                $presenter = Presenter::create($request->validated());
                $this->auditLog->logPresenterCreated($presenter);
                $plainPassword = $this->presenterAccountService->provisionAccount($presenter);

                return $presenter->fresh(['category', 'user']);
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (str_contains($exception->getMessage(), 'users_email_unique')) {
                return ApiResponse::error('Email sudah digunakan oleh user atau presenter lain.', 422, [
                    'email' => ['Email sudah digunakan oleh user atau presenter lain.'],
                ], 'VALIDATION_ERROR');
            }

            throw $exception;
        }

        $emailResult = $plainPassword
            ? $this->presenterAccountService->sendAccountEmail($presenter, $plainPassword)
            : ['success' => true];

        return ApiResponse::success([
            'presenter' => new PresenterResource($presenter),
            'account_email_sent' => $emailResult['success'],
            'email_message' => $emailResult['message'] ?? null,
        ], 'Presenter berhasil ditambahkan.', 201);
    }

    public function show(Presenter $presenter): JsonResponse
    {
        return ApiResponse::success(new PresenterResource($presenter->load(['category', 'user'])));
    }

    public function update(UpdatePresenterRequest $request, Presenter $presenter): JsonResponse
    {
        $oldAttributes = $presenter->toArray();
        $presenter->update($request->validated());

        if ($presenter->user) {
            $presenter->user->update([
                'name' => $presenter->name,
                'email' => $presenter->email,
                'phone' => $presenter->phone,
            ]);
        }

        $this->auditLog->logPresenterUpdated($presenter, $oldAttributes);

        return ApiResponse::success(new PresenterResource($presenter->fresh(['category', 'user'])), 'Presenter berhasil diperbarui.');
    }

    public function toggleStatus(Presenter $presenter): JsonResponse
    {
        $oldAttributes = $presenter->only(['status']);
        $newStatus = $presenter->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $presenter->update(['status' => $newStatus]);

        if ($newStatus === RecordStatus::Nonaktif) {
            $this->auditLog->logPresenterDeactivated($presenter, $oldAttributes);
        } else {
            $this->auditLog->logPresenterUpdated($presenter, $oldAttributes);
        }

        $message = $newStatus === RecordStatus::Aktif
            ? 'Presenter berhasil diaktifkan.'
            : 'Presenter berhasil dinonaktifkan.';

        return ApiResponse::success(new PresenterResource($presenter->fresh(['category', 'user'])), $message);
    }

    public function resendAccountEmail(Presenter $presenter): JsonResponse
    {
        $result = $this->presenterAccountService->resendAccountEmail($presenter);

        return ApiResponse::success(
            ['email_sent' => $result['success']],
            $result['message'],
            $result['success'] ? 200 : 422
        );
    }
}
