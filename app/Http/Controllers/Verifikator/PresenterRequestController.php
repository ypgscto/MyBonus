<?php

namespace App\Http\Controllers\Verifikator;

use App\Enums\PresenterRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Verifikator\ApprovePresenterRequestRequest;
use App\Http\Requests\Verifikator\RejectPresenterRequestRequest;
use App\Http\Requests\Verifikator\TransferToFinanceRequest;
use App\Models\PresenterRequest;
use App\Models\User;
use App\Services\CommissionCalculationService;
use App\Services\DashboardService;
use App\Services\VerifikatorTransferProofService;
use App\Services\VerifikatorWorkflowService;
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
        private readonly VerifikatorWorkflowService $workflow,
        private readonly CommissionCalculationService $commissionCalculator,
        private readonly VerifikatorTransferProofService $transferProofService,
        private readonly DashboardService $dashboard,
    ) {}

    public function dashboard(): View
    {
        Gate::authorize('verifikator-view-any');

        return view('verifikator.dashboard', $this->dashboard->verifikator());
    }

    public function pending(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::Submitted, 'Menunggu Verifikasi', 'verifikator.requests.pending');
    }

    public function approved(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::ApprovedByVerifikator, 'Permintaan Disetujui', 'verifikator.requests.approved');
    }

    public function rejected(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::RejectedByVerifikator, 'Permintaan Ditolak', 'verifikator.requests.rejected');
    }

    public function toTransfer(Request $request): View
    {
        return $this->index($request, PresenterRequestStatus::ApprovedByVerifikator, 'Transfer ke Keuangan', 'verifikator.requests.to-transfer');
    }

    public function transferHistory(Request $request): View
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
            ->paginate(15)
            ->withQueryString();

        return view('verifikator.requests.index', [
            'requests' => $requests,
            'title' => 'Riwayat Transfer ke Keuangan',
            'currentRoute' => 'verifikator.requests.transfer-history',
        ]);
    }

    public function show(PresenterRequest $presenterRequest): View
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
            'presenterTransfer',
        ]);

        $previewCommission = null;
        if ($presenterRequest->status === PresenterRequestStatus::Submitted) {
            try {
                $previewCommission = $this->commissionCalculator->calculate($presenterRequest);
            } catch (\Throwable) {
                $previewCommission = null;
            }
        }

        $financeUsers = User::query()
            ->keuanganWithBankAccount()
            ->where('status', UserStatus::Aktif)
            ->orderBy('name')
            ->get();

        $backRoute = match ($presenterRequest->status) {
            PresenterRequestStatus::Submitted => 'verifikator.requests.pending',
            PresenterRequestStatus::ApprovedByVerifikator => 'verifikator.requests.to-transfer',
            PresenterRequestStatus::RejectedByVerifikator => 'verifikator.requests.rejected',
            PresenterRequestStatus::TransferredToFinance => 'verifikator.requests.transfer-history',
            PresenterRequestStatus::ReceivedByFinance,
            PresenterRequestStatus::TransferredToPresenter,
            PresenterRequestStatus::Closed => 'verifikator.requests.transfer-history',
            default => 'verifikator.requests.pending',
        };

        return view('verifikator.requests.show', [
            'request' => $presenterRequest,
            'previewCommission' => $previewCommission,
            'financeUsers' => $financeUsers,
            'backRoute' => $backRoute,
        ]);
    }

    public function reject(RejectPresenterRequestRequest $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
    {
        Gate::authorize('verifikator-reject', $presenterRequest);

        $result = $this->workflow->reject($presenterRequest, $httpRequest->user(), $httpRequest->validated('rejection_reason'));

        return NotificationFlash::apply(
            redirect()
                ->route('verifikator.requests.rejected')
                ->with('status', 'Permintaan berhasil ditolak.'),
            $result->notifications
        );
    }

    public function approve(ApprovePresenterRequestRequest $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
    {
        Gate::authorize('verifikator-approve', $presenterRequest);

        $result = $this->workflow->approve($presenterRequest, $httpRequest->user(), $httpRequest->verifikator_note);

        return NotificationFlash::apply(
            redirect()
                ->route('verifikator.requests.to-transfer')
                ->with('status', 'Permintaan berhasil disetujui. Silakan lakukan transfer ke Keuangan.'),
            $result->notifications
        );
    }

    public function transfer(TransferToFinanceRequest $httpRequest, PresenterRequest $presenterRequest): RedirectResponse
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

        return NotificationFlash::apply(
            redirect()
                ->route('verifikator.requests.transfer-history')
                ->with('status', 'Transfer ke Keuangan berhasil.'),
            $result->notifications
        );
    }

    public function downloadTransferProof(PresenterRequest $presenterRequest): StreamedResponse
    {
        Gate::authorize('verifikator-view', $presenterRequest);
        Gate::authorize('verifikator-download-transfer-proof', $presenterRequest);

        $transfer = $presenterRequest->verifikatorTransfer;
        abort_unless($transfer?->transfer_proof_file, 404);
        abort_unless($this->transferProofService->exists($transfer->transfer_proof_file), 404);

        return Storage::disk('verifikator_transfers')->download($transfer->transfer_proof_file);
    }

    private function index(Request $request, PresenterRequestStatus $status, string $title, string $currentRoute): View
    {
        Gate::authorize('verifikator-view-any');

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
            ->paginate(15)
            ->withQueryString();

        return view('verifikator.requests.index', compact('requests', 'title', 'currentRoute'));
    }
}
