<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PresenterRequest\StorePresenterRequestDetailRequest;
use App\Http\Requests\PresenterRequest\UpdatePresenterRequestDetailRequest;
use App\Http\Resources\PresenterRequestDetailResource;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Services\AuditLogService;
use App\Services\PaymentProofService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresenterRequestDetailController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly PaymentProofService $paymentProofService,
    ) {}

    public function store(StorePresenterRequestDetailRequest $httpRequest, PresenterRequest $presenterRequest): JsonResponse
    {
        $this->authorize('manageDetails', $presenterRequest);

        $data = $httpRequest->validated();
        unset($data['payment_proof']);

        if ($httpRequest->hasFile('payment_proof')) {
            [$filename] = $this->paymentProofService->store($httpRequest->file('payment_proof'));
            $data['payment_proof_file'] = $filename;
        }

        $data['presenter_request_id'] = $presenterRequest->id;
        $detail = PresenterRequestDetail::create($data);

        $this->auditLog->logDraftUpdated($presenterRequest->fresh(), $presenterRequest->toArray(), [
            'detail_action' => 'added',
            'detail_id' => $detail->id,
        ]);

        return ApiResponse::success(
            new PresenterRequestDetailResource($detail),
            'Calon mahasiswa berhasil ditambahkan.',
            201
        );
    }

    public function update(
        UpdatePresenterRequestDetailRequest $httpRequest,
        PresenterRequest $presenterRequest,
        PresenterRequestDetail $detail,
    ): JsonResponse {
        $this->authorize('manageDetails', $presenterRequest);
        abort_unless($detail->presenter_request_id === $presenterRequest->id, 404);

        $oldAttributes = $detail->toArray();
        $data = $httpRequest->validated();
        unset($data['payment_proof']);

        if ($httpRequest->hasFile('payment_proof')) {
            $this->paymentProofService->delete($detail->payment_proof_file);
            [$filename] = $this->paymentProofService->store($httpRequest->file('payment_proof'));
            $data['payment_proof_file'] = $filename;
        }

        $detail->update($data);

        $this->auditLog->logDraftUpdated($presenterRequest->fresh(), $presenterRequest->toArray(), [
            'detail_action' => 'updated',
            'detail_id' => $detail->id,
            'old_detail' => $oldAttributes,
        ]);

        return ApiResponse::success(new PresenterRequestDetailResource($detail->fresh()), 'Data calon mahasiswa berhasil diperbarui.');
    }

    public function destroy(PresenterRequest $presenterRequest, PresenterRequestDetail $detail): JsonResponse
    {
        $this->authorize('manageDetails', $presenterRequest);
        abort_unless($detail->presenter_request_id === $presenterRequest->id, 404);

        $detailId = $detail->id;
        $oldAttributes = $detail->toArray();
        $this->paymentProofService->delete($detail->payment_proof_file);
        $detail->delete();

        $this->auditLog->logDraftUpdated($presenterRequest->fresh(), $presenterRequest->toArray(), [
            'detail_action' => 'deleted',
            'detail_id' => $detailId,
            'old_detail' => $oldAttributes,
        ]);

        return ApiResponse::success(null, 'Calon mahasiswa berhasil dihapus.');
    }

    public function downloadPaymentProof(PresenterRequestDetail $detail): StreamedResponse|JsonResponse
    {
        $presenterRequest = $detail->presenterRequest;
        Gate::authorize('download-payment-proof', $presenterRequest);

        if (! $detail->payment_proof_file || ! $this->paymentProofService->exists($detail->payment_proof_file)) {
            return ApiResponse::error('Bukti pembayaran tidak ditemukan.', 404, code: 'PROOF_NOT_FOUND');
        }

        return Storage::disk('payment_proofs')->download($detail->payment_proof_file);
    }
}
