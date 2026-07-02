<?php

namespace App\Http\Controllers\PresenterRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\PresenterRequest\StorePresenterRequestDetailRequest;
use App\Http\Requests\PresenterRequest\UpdatePresenterRequestDetailRequest;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Services\AuditLogService;
use App\Services\PaymentProofService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresenterRequestDetailController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly PaymentProofService $paymentProofService,
    ) {}

    public function store(StorePresenterRequestDetailRequest $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
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

        return redirect()
            ->route('presenter-requests.edit', $presenterRequest)
            ->with('status', 'Calon mahasiswa berhasil ditambahkan.');
    }

    public function update(
        UpdatePresenterRequestDetailRequest $httpRequest,
        PresenterRequest $presenterRequest,
        PresenterRequestDetail $detail,
    ): RedirectResponse {
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

        return redirect()
            ->route('presenter-requests.edit', $presenterRequest)
            ->with('status', 'Data calon mahasiswa berhasil diperbarui.');
    }

    public function destroy(PresenterRequest $presenterRequest, PresenterRequestDetail $detail): RedirectResponse
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

        return redirect()
            ->route('presenter-requests.edit', $presenterRequest)
            ->with('status', 'Calon mahasiswa berhasil dihapus.');
    }

    public function download(PresenterRequestDetail $detail): StreamedResponse
    {
        $presenterRequest = $detail->presenterRequest;
        Gate::authorize('download-payment-proof', $presenterRequest);

        abort_unless($detail->payment_proof_file, 404);
        abort_unless($this->paymentProofService->exists($detail->payment_proof_file), 404);

        return Storage::disk('payment_proofs')->download($detail->payment_proof_file);
    }
}
