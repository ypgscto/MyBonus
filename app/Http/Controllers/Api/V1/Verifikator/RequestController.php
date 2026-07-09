<?php

namespace App\Http\Controllers\Api\V1\Verifikator;

use App\Enums\PresenterRequestStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Verifikator\ApprovePresenterRequestRequest;
use App\Http\Requests\Verifikator\RejectPresenterRequestRequest;
use App\Http\Requests\Verifikator\TransferToFinanceRequest;
use App\Http\Resources\PresenterRequestResource;
use App\Http\Resources\UserResource;
use App\Models\PresenterRequest;
use App\Models\User;
use App\Services\CommissionCalculationService;
use App\Services\VerifikatorTransferProofService;
use App\Services\VerifikatorWorkflowService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestController extends Controller
{
    use PaginatesApiRequests;

    public function __construct(
        private readonly VerifikatorWorkflowService $workflow,
        private readonly CommissionCalculationService $commissionCalculator,
        private readonly VerifikatorTransferProofService $transferProofService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('verifikator-view-any');

        $status = $request->enum('status', PresenterRequestStatus::class)
            ?? PresenterRequestStatus::Submitted;

        $requests = PresenterRequest::query()
            ->with(['presenter.category', 'pmbPeriod'])
            ->where('status', $status)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('presenter', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function transferHistory(Request $request): JsonResponse
    {
        Gate::authorize('verifikator-view-any');

        $requests = PresenterRequest::query()
            ->with(['presenter.category', 'pmbPeriod', 'verifikatorTransfer'])
            ->where('status', PresenterRequestStatus::TransferredToFinance)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('presenter', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('transferred_to_finance_at')
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function show(PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('verifikator-view', $presenterRequest);

        $presenterRequest->load([
            'presenter.category',
            'pmbPeriod',
            'details',
            'creator',
            'approver',
            'rejector',
            'verifikatorTransfer.financeUser',
            'verifikatorTransfer.transferrer',
        ]);

        $previewCommission = null;
        if ($presenterRequest->status === PresenterRequestStatus::Submitted) {
            try {
                $previewCommission = $this->commissionCalculator->calculate($presenterRequest);
            } catch (\Throwable) {
                $previewCommission = null;
            }
        }

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest, includeBankNote: true),
            'preview_commission' => $previewCommission,
        ]);
    }

    public function bankTransferNote(PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('verifikator-view', $presenterRequest);

        $presenterRequest->load('details');

        return ApiResponse::success([
            'bank_transfer_note' => $presenterRequest->bankTransferNote(),
        ]);
    }

    public function financeUsers(): JsonResponse
    {
        Gate::authorize('verifikator-view-any');

        $users = User::query()
            ->keuanganWithBankAccount()
            ->where('status', UserStatus::Aktif)
            ->orderBy('name')
            ->get();

        return ApiResponse::success(UserResource::collection($users));
    }

    public function approve(ApprovePresenterRequestRequest $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('verifikator-approve', $presenterRequest);

        $result = $this->workflow->approve(
            $presenterRequest,
            $httpRequest->user(),
            $httpRequest->verifikator_note
        );

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest->fresh()),
            'notifications' => $result->notifications,
        ], 'Permintaan berhasil disetujui.');
    }

    public function reject(RejectPresenterRequestRequest $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('verifikator-reject', $presenterRequest);

        $result = $this->workflow->reject(
            $presenterRequest,
            $httpRequest->user(),
            $httpRequest->validated('rejection_reason')
        );

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest->fresh()),
            'notifications' => $result->notifications,
        ], 'Permintaan berhasil ditolak.');
    }

    public function transfer(TransferToFinanceRequest $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('verifikator-transfer', $presenterRequest);

        [$proofFile] = $this->transferProofService->store($httpRequest->file('transfer_proof'));

        try {
            $financeUser = $httpRequest->financeUser();

            $result = $this->workflow->transferToFinance($presenterRequest, $httpRequest->user(), [
                'transfer_date' => $httpRequest->transfer_date,
                'transfer_amount' => $httpRequest->transfer_amount,
                'finance_user_id' => $financeUser->id,
                'destination_bank' => $financeUser->bank_name,
                'destination_account_number' => $financeUser->account_number,
                'destination_account_name' => $financeUser->account_holder_name,
                'transfer_proof_file' => $proofFile,
                'note' => $httpRequest->note,
            ]);
        } catch (\Throwable $e) {
            $this->transferProofService->delete($proofFile);
            throw $e;
        }

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest->fresh()),
            'notifications' => $result->notifications,
        ], 'Transfer ke Keuangan berhasil.');
    }

    public function downloadTransferProof(PresenterRequest $presenterRequest): StreamedResponse|JsonResponse
    {
        Gate::authorize('verifikator-view', $presenterRequest);
        Gate::authorize('verifikator-download-transfer-proof', $presenterRequest);

        $transfer = $presenterRequest->verifikatorTransfer;

        if (! $transfer?->transfer_proof_file || ! $this->transferProofService->exists($transfer->transfer_proof_file)) {
            return ApiResponse::error('Bukti transfer tidak ditemukan.', 404, code: 'PROOF_NOT_FOUND');
        }

        return Storage::disk('verifikator_transfers')->download($transfer->transfer_proof_file);
    }
}
