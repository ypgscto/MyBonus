<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PresenterRequestStatus;
use App\Enums\UserRole;
use App\Exceptions\DuplicateNimValidationException;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PresenterRequest\StorePresenterRequestWithDetailsRequest;
use App\Http\Requests\PresenterRequest\UpdatePresenterRequestHeaderRequest;
use App\Http\Resources\PresenterRequestResource;
use App\Http\Resources\PresenterResource;
use App\Models\Presenter;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Services\AuditLogService;
use App\Services\CommissionCalculationService;
use App\Services\DuplicateNimValidatorService;
use App\Services\PaymentProofService;
use App\Services\PresenterRequestSubmitService;
use App\Support\ApiResponse;
use App\Support\RequestCodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PresenterRequestController extends Controller
{
    use PaginatesApiRequests;

    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly RequestCodeGenerator $requestCodeGenerator,
        private readonly PresenterRequestSubmitService $submitService,
        private readonly PaymentProofService $paymentProofService,
        private readonly CommissionCalculationService $commissionCalculator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PresenterRequest::class);

        $requests = $this->baseQuery($request)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('submitted_at')
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function drafts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PresenterRequest::class);

        $requests = $this->baseQuery($request)
            ->draft()
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PresenterRequest::class);

        $requests = $this->baseQuery($request)
            ->history()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('submitted_at')
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function store(StorePresenterRequestWithDetailsRequest $request): JsonResponse
    {
        $this->authorize('create', PresenterRequest::class);

        $presenterRequest = PresenterRequest::create([
            'request_code' => $this->requestCodeGenerator->generate(),
            'pmb_period_id' => $request->pmb_period_id,
            'presenter_id' => $request->presenter_id,
            'created_by' => $request->user()->id,
            'status' => PresenterRequestStatus::Draft,
            'request_date' => now()->toDateString(),
            'admin_note' => $request->admin_note,
        ]);

        $this->auditLog->logDraftCreated($presenterRequest);
        $this->persistDetails($request, $presenterRequest);

        if ($request->input('action') === 'submit') {
            try {
                $result = $this->submitService->submit($presenterRequest->fresh(), $request->user()->id);

                return ApiResponse::success([
                    'request' => new PresenterRequestResource($presenterRequest->fresh()->load(['presenter.category', 'pmbPeriod', 'details'])),
                    'notifications' => $result->notifications,
                ], 'Permintaan berhasil dikirim ke Verifikator.', 201);
            } catch (DuplicateNimValidationException $e) {
                $this->auditLog->logDuplicateNimFailed($presenterRequest, $e->report);

                return ApiResponse::error($e->getMessage(), 422, [
                    'duplicate_nim_report' => $e->report,
                ], 'DUPLICATE_NIM');
            } catch (ValidationException $e) {
                return ApiResponse::error('Permintaan gagal dikirim.', 422, $e->errors(), 'VALIDATION_ERROR');
            }
        }

        return ApiResponse::success(
            new PresenterRequestResource($presenterRequest->fresh()->load(['presenter.category', 'pmbPeriod', 'details'])),
            'Draft permintaan berhasil disimpan.',
            201
        );
    }

    public function show(PresenterRequest $presenterRequest): JsonResponse
    {
        $this->authorize('view', $presenterRequest);

        $presenterRequest->load(['presenter.category', 'pmbPeriod', 'details', 'submitter', 'creator']);

        return ApiResponse::success(new PresenterRequestResource($presenterRequest));
    }

    public function update(UpdatePresenterRequestHeaderRequest $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        $this->authorize('update', $presenterRequest);

        $oldAttributes = $presenterRequest->toArray();
        $presenterRequest->update($httpRequest->validated());
        $this->auditLog->logDraftUpdated($presenterRequest, $oldAttributes);

        return ApiResponse::success(
            new PresenterRequestResource($presenterRequest->fresh()->load(['presenter.category', 'pmbPeriod', 'details'])),
            'Data permintaan berhasil diperbarui.'
        );
    }

    public function submit(Request $request, PresenterRequest $presenterRequest): JsonResponse
    {
        $this->authorize('submit', $presenterRequest);

        try {
            $result = $this->submitService->submit($presenterRequest, $request->user()->id);
        } catch (DuplicateNimValidationException $e) {
            $this->auditLog->logDuplicateNimFailed($presenterRequest, $e->report);

            return ApiResponse::error($e->getMessage(), 422, [
                'duplicate_nim_report' => $e->report,
            ], 'DUPLICATE_NIM');
        } catch (ValidationException $e) {
            return ApiResponse::error('Permintaan gagal dikirim.', 422, $e->errors(), 'VALIDATION_ERROR');
        }

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest->fresh()),
            'notifications' => $result->notifications,
        ], 'Permintaan berhasil dikirim ke Verifikator.');
    }

    public function checkNim(Request $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        $this->authorize('update', $presenterRequest);

        $nim = trim($httpRequest->string('nim')->toString());
        $excludeDetailId = $httpRequest->integer('exclude_detail_id') ?: null;

        if ($nim === '') {
            return ApiResponse::success([
                'valid' => false,
                'message' => 'NIM wajib diisi.',
            ]);
        }

        $validator = app(DuplicateNimValidatorService::class);

        $existingDetails = $presenterRequest->details()
            ->when($excludeDetailId, fn ($q) => $q->where('id', '!=', $excludeDetailId))
            ->get()
            ->each(fn ($detail) => $detail->setRelation('presenterRequest', $presenterRequest));

        $simulated = $existingDetails->push(new PresenterRequestDetail([
            'nim' => $nim,
            'student_name' => $httpRequest->string('student_name')->toString() ?: '-',
        ]));

        $within = $validator->validateWithinCurrentRequest($simulated);
        $blocking = $validator->getBlockingConflictsOnly($presenterRequest->id, [$nim]);
        $warnings = $validator->getDraftWarningsOnly($presenterRequest->id, [$nim]);

        return ApiResponse::success([
            'valid' => empty($within) && empty($blocking),
            'within_current' => ! empty($within),
            'blocking' => $blocking,
            'warnings' => $warnings,
        ]);
    }

    public function commissionPreview(Request $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        $this->authorize('view', $presenterRequest);

        $preview = $this->commissionCalculator->preview(
            $presenterRequest,
            $httpRequest->integer('presenter_id') ?: null,
            $httpRequest->integer('pmb_period_id') ?: null,
        );

        return ApiResponse::success($preview);
    }

    public function presenterInfo(Presenter $presenter): JsonResponse
    {
        $this->authorize('create', PresenterRequest::class);

        return ApiResponse::success(new PresenterResource($presenter->load('category')));
    }

    private function baseQuery(Request $request)
    {
        $user = $request->user();

        return PresenterRequest::query()
            ->with(['presenter.category', 'pmbPeriod', 'creator'])
            ->withCount('details')
            ->when($user->role !== UserRole::SuperAdmin, fn ($q) => $q->where('created_by', $user->id))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('presenter', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            });
    }

    private function persistDetails(StorePresenterRequestWithDetailsRequest $request, PresenterRequest $presenterRequest): void
    {
        foreach ($request->input('details', []) as $index => $row) {
            if (empty($row['nim']) && empty($row['student_name'])) {
                continue;
            }

            $data = [
                'presenter_request_id' => $presenterRequest->id,
                'nim' => $row['nim'],
                'student_name' => $row['student_name'],
                'birth_date' => $row['birth_date'] ?? null,
                'payment_date' => $row['payment_date'] ?? null,
                'note' => $row['note'] ?? null,
            ];

            $file = $request->file("details.{$index}.payment_proof");
            if ($file) {
                [$filename] = $this->paymentProofService->store($file);
                $data['payment_proof_file'] = $filename;
            }

            PresenterRequestDetail::create($data);
        }

        if ($presenterRequest->details()->exists()) {
            $this->auditLog->logDraftUpdated($presenterRequest->fresh(), $presenterRequest->toArray(), [
                'detail_action' => 'bulk_added_on_create',
            ]);
        }
    }
}
