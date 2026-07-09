<?php

namespace App\Http\Controllers\Api\V1\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePresenterCategoryRequest;
use App\Http\Requests\Master\UpdatePresenterCategoryRequest;
use App\Models\PresenterCategory;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenterCategoryController extends Controller
{
    use PaginatesApiRequests;

    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): JsonResponse
    {
        $categories = PresenterCategory::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success($categories->toArray());
    }

    public function store(StorePresenterCategoryRequest $request): JsonResponse
    {
        $category = PresenterCategory::create($request->validated());
        $this->auditLog->logPresenterCategoryCreated($category);

        return ApiResponse::success($category, 'Kategori presenter berhasil ditambahkan.', 201);
    }

    public function show(PresenterCategory $presenterCategory): JsonResponse
    {
        return ApiResponse::success($presenterCategory);
    }

    public function update(UpdatePresenterCategoryRequest $request, PresenterCategory $presenterCategory): JsonResponse
    {
        $oldAttributes = $presenterCategory->toArray();
        $presenterCategory->update($request->validated());
        $this->auditLog->logPresenterCategoryUpdated($presenterCategory, $oldAttributes);

        return ApiResponse::success($presenterCategory->fresh(), 'Kategori presenter berhasil diperbarui.');
    }

    public function toggleStatus(PresenterCategory $presenterCategory): JsonResponse
    {
        $oldAttributes = $presenterCategory->only(['status']);
        $newStatus = $presenterCategory->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $presenterCategory->update(['status' => $newStatus]);

        if ($newStatus === RecordStatus::Nonaktif) {
            $this->auditLog->logPresenterCategoryDeactivated($presenterCategory, $oldAttributes);
        } else {
            $this->auditLog->logPresenterCategoryUpdated($presenterCategory, $oldAttributes);
        }

        $message = $newStatus === RecordStatus::Aktif
            ? 'Kategori presenter berhasil diaktifkan.'
            : 'Kategori presenter berhasil dinonaktifkan.';

        return ApiResponse::success($presenterCategory->fresh(), $message);
    }
}
