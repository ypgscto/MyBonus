<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Enums\PresenterRequestStatus;
use App\Http\Controllers\Api\Concerns\PaginatesApiRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Keuangan\TransferToPresenterRequest;
use App\Http\Resources\PresenterRequestResource;
use App\Models\PresenterRequest;
use App\Services\KeuanganWorkflowService;
use App\Services\PresenterTransferProofService;
use App\Services\VerifikatorTransferProofService;
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
        private readonly KeuanganWorkflowService $workflow,
        private readonly VerifikatorTransferProofService $verifikatorProofService,
        private readonly PresenterTransferProofService $presenterProofService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('keuangan-view-any');

        $status = $request->enum('status', PresenterRequestStatus::class)
            ?? PresenterRequestStatus::TransferredToFinance;

        $requests = PresenterRequest::query()
            ->with(['presenter', 'pmbPeriod', 'verifikatorTransfer'])
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

    public function disbursementHistory(Request $request): JsonResponse
    {
        Gate::authorize('keuangan-view-any');

        $requests = PresenterRequest::query()
            ->with(['presenter', 'pmbPeriod', 'presenterTransfer'])
            ->where('status', PresenterRequestStatus::TransferredToPresenter)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('request_code', 'like', "%{$search}%")
                        ->orWhereHas('presenter', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('transferred_to_presenter_at')
            ->paginate($this->perPage($request));

        return ApiResponse::success(
            PresenterRequestResource::collection($requests)->response()->getData(true)
        );
    }

    public function show(PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('keuangan-view', $presenterRequest);

        $presenterRequest->load([
            'presenter.category',
            'pmbPeriod',
            'details',
            'verifikatorTransfer.transferrer',
            'presenterTransfer.transferrer',
            'financeReceiver',
        ]);

        return ApiResponse::success(new PresenterRequestResource($presenterRequest, includeBankNote: true));
    }

    public function bankTransferNote(PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('keuangan-view', $presenterRequest);

        $presenterRequest->load('details');

        return ApiResponse::success([
            'request_code' => $presenterRequest->request_code,
            'bank_transfer_note' => $presenterRequest->bankTransferNote(),
        ]);
    }

    public function confirmReceived(Request $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('keuangan-confirm-received', $presenterRequest);

        $result = $this->workflow->confirmReceived($presenterRequest, $httpRequest->user());

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest->fresh()),
            'notifications' => $result->notifications,
        ], 'Dana berhasil dikonfirmasi diterima.');
    }

    public function transfer(TransferToPresenterRequest $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('keuangan-transfer-to-presenter', $presenterRequest);

        [$proofFile] = $this->presenterProofService->store($httpRequest->file('transfer_proof'));

        try {
            $bank = $httpRequest->presenterBankDetails();

            $result = $this->workflow->transferToPresenter($presenterRequest, $httpRequest->user(), [
                'transfer_date' => $httpRequest->transfer_date,
                'transfer_amount' => $httpRequest->transfer_amount,
                'destination_bank' => $bank['bank'],
                'destination_account_number' => $bank['account_number'],
                'destination_account_name' => $bank['account_holder_name'],
                'transfer_proof_file' => $proofFile,
                'note' => $httpRequest->note,
                'finance_note' => $httpRequest->finance_note,
            ]);
        } catch (\Throwable $e) {
            $this->presenterProofService->delete($proofFile);
            throw $e;
        }

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest->fresh()),
            'notifications' => $result->notifications,
        ], 'Transfer ke Presenter berhasil.');
    }

    public function close(Request $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        Gate::authorize('keuangan-close', $presenterRequest);

        $result = $this->workflow->close($presenterRequest, $httpRequest->user());

        return ApiResponse::success([
            'request' => new PresenterRequestResource($presenterRequest->fresh()),
            'notifications' => $result->notifications,
        ], 'Permintaan berhasil ditutup.');
    }

    public function downloadVerifikatorProof(PresenterRequest $presenterRequest): StreamedResponse|JsonResponse
    {
        Gate::authorize('keuangan-view', $presenterRequest);
        Gate::authorize('keuangan-download-verifikator-proof', $presenterRequest);

        $transfer = $presenterRequest->verifikatorTransfer;

        if (! $transfer?->transfer_proof_file || ! $this->verifikatorProofService->exists($transfer->transfer_proof_file)) {
            return ApiResponse::error('Bukti transfer verifikator tidak ditemukan.', 404, code: 'PROOF_NOT_FOUND');
        }

        return Storage::disk('verifikator_transfers')->download($transfer->transfer_proof_file);
    }

    public function downloadPresenterProof(PresenterRequest $presenterRequest): StreamedResponse|JsonResponse
    {
        Gate::authorize('keuangan-view', $presenterRequest);
        Gate::authorize('keuangan-download-presenter-proof', $presenterRequest);

        $transfer = $presenterRequest->presenterTransfer;

        if (! $transfer?->transfer_proof_file || ! $this->presenterProofService->exists($transfer->transfer_proof_file)) {
            return ApiResponse::error('Bukti transfer presenter tidak ditemukan.', 404, code: 'PROOF_NOT_FOUND');
        }

        return Storage::disk('presenter_transfers')->download($transfer->transfer_proof_file);
    }
}
