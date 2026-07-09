<?php

namespace App\Http\Controllers\Api\V1\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCommissionSchemeRequest;
use App\Http\Requests\Master\UpdateCommissionSchemeRequest;
use App\Models\CommissionScheme;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionSchemeController extends Controller
{
    use PaginatesApiRequests;

    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): JsonResponse
    {
        $schemes = CommissionScheme::query()
            ->with(['presenterCategory', 'pmbPeriod'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->whereHas('presenterCategory', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('pmbPeriod', function ($pq) use ($search) {
                            $pq->where('academic_year', 'like', "%{$search}%")
                                ->orWhere('wave', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success($schemes->toArray());
    }

    public function store(StoreCommissionSchemeRequest $request): JsonResponse
    {
        $scheme = CommissionScheme::create($request->validated());
        $this->auditLog->logCommissionSchemeCreated($scheme);

        return ApiResponse::success($scheme->load(['presenterCategory', 'pmbPeriod']), 'Skema komisi berhasil ditambahkan.', 201);
    }

    public function show(CommissionScheme $commissionScheme): JsonResponse
    {
        return ApiResponse::success($commissionScheme->load(['presenterCategory', 'pmbPeriod']));
    }

    public function update(UpdateCommissionSchemeRequest $request, CommissionScheme $commissionScheme): JsonResponse
    {
        $commissionScheme->update($request->validated());

        return ApiResponse::success($commissionScheme->fresh(['presenterCategory', 'pmbPeriod']), 'Skema komisi berhasil diperbarui.');
    }

    public function toggleStatus(CommissionScheme $commissionScheme): JsonResponse
    {
        $newStatus = $commissionScheme->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $commissionScheme->update(['status' => $newStatus]);

        $message = $newStatus === RecordStatus::Aktif
            ? 'Skema komisi berhasil diaktifkan.'
            : 'Skema komisi berhasil dinonaktifkan.';

        return ApiResponse::success($commissionScheme->fresh(['presenterCategory', 'pmbPeriod']), $message);
    }
}
