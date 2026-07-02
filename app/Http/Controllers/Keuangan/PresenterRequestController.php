<?php

namespace App\Http\Controllers\Keuangan;

use App\Enums\PresenterRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Keuangan\TransferToPresenterRequest;
use App\Models\PresenterRequest;
use App\Services\DashboardService;
use App\Services\KeuanganWorkflowService;
use App\Services\PresenterTransferProofService;
use App\Services\VerifikatorTransferProofService;
use App\Support\NotificationFlash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresenterRequestController extends Controller
{
    public function __construct(
        private readonly KeuanganWorkflowService $workflow,
        private readonly VerifikatorTransferProofService $verifikatorProofService,
        private readonly PresenterTransferProofService $presenterProofService,
        private readonly DashboardService $dashboard,
    ) {}

    public function dashboard(): View
    {
        Gate::authorize('keuangan-view-any');

        return view('keuangan.dashboard', $this->dashboard->keuangan());
    }

    public function incoming(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::TransferredToFinance, 'Dana Masuk dari Verifikator', 'keuangan.requests.incoming');
    }

    public function received(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::ReceivedByFinance, 'Konfirmasi Dana Diterima', 'keuangan.requests.received');
    }

    public function toTransfer(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::ReceivedByFinance, 'Transfer ke Presenter', 'keuangan.requests.to-transfer');
    }

    public function closed(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::Closed, 'Permintaan Closed', 'keuangan.requests.closed');
    }

    public function disbursementHistory(Request $request): View
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
            ->paginate(15)
            ->withQueryString();

        return view('keuangan.requests.index', [
            'requests' => $requests,
            'title' => 'Riwayat Pencairan',
            'currentRoute' => 'keuangan.requests.disbursement-history',
        ]);
    }

    public function show(PresenterRequest $presenterRequest): View
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

        $backRoute = match ($presenterRequest->status) {
            PresenterRequestStatus::TransferredToFinance => 'keuangan.requests.incoming',
            PresenterRequestStatus::ReceivedByFinance => 'keuangan.requests.to-transfer',
            PresenterRequestStatus::TransferredToPresenter => 'keuangan.requests.disbursement-history',
            PresenterRequestStatus::Closed => 'keuangan.requests.closed',
            default => 'keuangan.requests.incoming',
        };

        return view('keuangan.requests.show', [
            'request' => $presenterRequest,
            'backRoute' => $backRoute,
        ]);
    }

    public function confirmReceived(Request $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
    {
        Gate::authorize('keuangan-confirm-received', $presenterRequest);

        $result = $this->workflow->confirmReceived($presenterRequest, $httpRequest->user());

        return NotificationFlash::apply(
            redirect()
                ->route('keuangan.requests.to-transfer')
                ->with('status', 'Dana berhasil dikonfirmasi diterima. Silakan lakukan transfer ke Presenter.'),
            $result->notifications
        );
    }

    public function transfer(TransferToPresenterRequest $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
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

        return NotificationFlash::apply(
            redirect()
                ->route('keuangan.requests.disbursement-history')
                ->with('status', 'Transfer ke Presenter berhasil.'),
            $result->notifications
        );
    }

    public function close(Request $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
    {
        Gate::authorize('keuangan-close', $presenterRequest);

        $result = $this->workflow->close($presenterRequest, $httpRequest->user());

        return NotificationFlash::apply(
            redirect()
                ->route('keuangan.requests.closed')
                ->with('status', 'Permintaan berhasil ditutup.'),
            $result->notifications
        );
    }

    public function downloadVerifikatorProof(PresenterRequest $presenterRequest): StreamedResponse
    {
        Gate::authorize('keuangan-view', $presenterRequest);
        Gate::authorize('keuangan-download-verifikator-proof', $presenterRequest);

        $transfer = $presenterRequest->verifikatorTransfer;
        abort_unless($transfer?->transfer_proof_file, 404);
        abort_unless($this->verifikatorProofService->exists($transfer->transfer_proof_file), 404);

        return Storage::disk('verifikator_transfers')->download($transfer->transfer_proof_file);
    }

    public function downloadPresenterProof(PresenterRequest $presenterRequest): StreamedResponse
    {
        Gate::authorize('keuangan-view', $presenterRequest);
        Gate::authorize('keuangan-download-presenter-proof', $presenterRequest);

        $transfer = $presenterRequest->presenterTransfer;
        abort_unless($transfer?->transfer_proof_file, 404);
        abort_unless($this->presenterProofService->exists($transfer->transfer_proof_file), 404);

        return Storage::disk('presenter_transfers')->download($transfer->transfer_proof_file);
    }

    private function index(Request $request, PresenterRequestStatus $status, string $title, string $currentRoute): View
    {
        Gate::authorize('keuangan-view-any');

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
            ->paginate(15)
            ->withQueryString();

        return view('keuangan.requests.index', compact('requests', 'title', 'currentRoute'));
    }
}
