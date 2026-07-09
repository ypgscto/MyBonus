<?php

namespace App\Http\Controllers\Api\V1\Master;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StorePmbPeriodRequest;
use App\Http\Requests\Master\UpdatePmbPeriodRequest;
use App\Models\PmbPeriod;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PmbPeriodController extends Controller
{
    use PaginatesApiRequests;

    public function __construct(private readonly AuditLogService $auditLog) {}

    public function index(Request $request): JsonResponse
    {
        $periods = PmbPeriod::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('academic_year', 'like', "%{$search}%")
                        ->orWhere('wave', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success($periods->toArray());
    }

    public function store(StorePmbPeriodRequest $request): JsonResponse
    {
        $period = PmbPeriod::create($request->validated());
        $this->auditLog->logPmbPeriodCreated($period);

        return ApiResponse::success($period, 'Periode PMB berhasil ditambahkan.', 201);
    }

    public function show(PmbPeriod $pmbPeriod): JsonResponse
    {
        return ApiResponse::success($pmbPeriod);
    }

    public function update(UpdatePmbPeriodRequest $request, PmbPeriod $pmbPeriod): JsonResponse
    {
        $pmbPeriod->update($request->validated());

        return ApiResponse::success($pmbPeriod->fresh(), 'Periode PMB berhasil diperbarui.');
    }

    public function toggleStatus(PmbPeriod $pmbPeriod): JsonResponse
    {
        $newStatus = $pmbPeriod->status === RecordStatus::Aktif
            ? RecordStatus::Nonaktif
            : RecordStatus::Aktif;

        $pmbPeriod->update(['status' => $newStatus]);

        $message = $newStatus === RecordStatus::Aktif
            ? 'Periode PMB berhasil diaktifkan.'
            : 'Periode PMB berhasil dinonaktifkan.';

        return ApiResponse::success($pmbPeriod->fresh(), $message);
    }
}
