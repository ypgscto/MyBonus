<?php

namespace App\Services;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Models\Presenter;
use App\Models\PresenterRequest;
use App\Models\PresenterTransfer;
use App\Models\User;
use App\Models\VerifikatorTransfer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function superAdmin(): array
    {
        $activePresenters = Presenter::where('status', RecordStatus::Aktif)->count();
        $totalRequests = PresenterRequest::where('status', '!=', PresenterRequestStatus::Draft)->count();
        $totalStudents = (int) PresenterRequest::where('status', '!=', PresenterRequestStatus::Draft)->sum('total_students');
        $totalCommission = (float) PresenterRequest::where('status', '!=', PresenterRequestStatus::Draft)->sum('total_commission');
        $totalVerifikatorTransfers = (float) VerifikatorTransfer::sum('transfer_amount');
        $totalPresenterTransfers = (float) PresenterTransfer::sum('transfer_amount');
        $totalClosed = PresenterRequest::where('status', PresenterRequestStatus::Closed)->count();
        $pendingVerification = PresenterRequest::where('status', PresenterRequestStatus::Submitted)->count();
        $pendingDisbursement = PresenterRequest::whereIn('status', [
            PresenterRequestStatus::TransferredToFinance,
            PresenterRequestStatus::ReceivedByFinance,
            PresenterRequestStatus::ApprovedByVerifikator,
        ])->count();
        $monthlyCommission = (float) PresenterRequest::query()
            ->where('status', '!=', PresenterRequestStatus::Draft)
            ->whereYear('submitted_at', now()->year)
            ->whereMonth('submitted_at', now()->month)
            ->sum('total_commission');

        $requestsPerMonth = $this->requestsPerMonth();
        $topPresentersByStudents = $this->topPresentersByStudents();
        $topPresentersByCommission = $this->topPresentersByCommission();

        return compact(
            'activePresenters',
            'totalRequests',
            'totalStudents',
            'totalCommission',
            'totalVerifikatorTransfers',
            'totalPresenterTransfers',
            'totalClosed',
            'pendingVerification',
            'pendingDisbursement',
            'monthlyCommission',
            'requestsPerMonth',
            'topPresentersByStudents',
            'topPresentersByCommission',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function adminPmb(User $user): array
    {
        $base = PresenterRequest::query()->where('created_by', $user->id);

        $counts = [
            'draft' => (clone $base)->where('status', PresenterRequestStatus::Draft)->count(),
            'submitted' => (clone $base)->where('status', PresenterRequestStatus::Submitted)->count(),
            'rejected' => (clone $base)->where('status', PresenterRequestStatus::RejectedByVerifikator)->count(),
            'approved' => (clone $base)->where('status', PresenterRequestStatus::ApprovedByVerifikator)->count(),
            'transferred_to_finance' => (clone $base)->where('status', PresenterRequestStatus::TransferredToFinance)->count(),
            'closed' => (clone $base)->where('status', PresenterRequestStatus::Closed)->count(),
        ];

        $recentRequests = (clone $base)
            ->with(['presenter', 'pmbPeriod'])
            ->where('status', '!=', PresenterRequestStatus::Draft)
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        $rejectedRequests = (clone $base)
            ->with(['presenter'])
            ->where('status', PresenterRequestStatus::RejectedByVerifikator)
            ->latest('rejected_at')
            ->limit(10)
            ->get();

        return compact('counts', 'recentRequests', 'rejectedRequests');
    }

    /**
     * @return array<string, mixed>
     */
    public function verifikator(): array
    {
        $counts = [
            'pending' => PresenterRequest::where('status', PresenterRequestStatus::Submitted)->count(),
            'approved' => PresenterRequest::where('status', PresenterRequestStatus::ApprovedByVerifikator)->count(),
            'rejected' => PresenterRequest::where('status', PresenterRequestStatus::RejectedByVerifikator)->count(),
            'transferred_to_finance' => PresenterRequest::whereIn('status', [
                PresenterRequestStatus::TransferredToFinance,
                PresenterRequestStatus::ReceivedByFinance,
                PresenterRequestStatus::TransferredToPresenter,
                PresenterRequestStatus::Closed,
            ])->count(),
        ];

        $monthlyTransferAmount = (float) VerifikatorTransfer::query()
            ->whereYear('transfer_date', now()->year)
            ->whereMonth('transfer_date', now()->month)
            ->sum('transfer_amount');

        $pendingRequests = PresenterRequest::query()
            ->with(['presenter', 'pmbPeriod'])
            ->where('status', PresenterRequestStatus::Submitted)
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        return compact('counts', 'monthlyTransferAmount', 'pendingRequests');
    }

    /**
     * @return array<string, mixed>
     */
    public function keuangan(): array
    {
        $counts = [
            'awaiting_confirmation' => PresenterRequest::where('status', PresenterRequestStatus::TransferredToFinance)->count(),
            'received' => PresenterRequest::whereNotNull('received_by_finance_at')->count(),
            'awaiting_presenter_transfer' => PresenterRequest::where('status', PresenterRequestStatus::ReceivedByFinance)->count(),
            'transferred_to_presenter' => PresenterRequest::whereIn('status', [
                PresenterRequestStatus::TransferredToPresenter,
                PresenterRequestStatus::Closed,
            ])->count(),
            'closed' => PresenterRequest::where('status', PresenterRequestStatus::Closed)->count(),
        ];

        $monthlyTransferAmount = (float) PresenterTransfer::query()
            ->whereYear('transfer_date', now()->year)
            ->whereMonth('transfer_date', now()->month)
            ->sum('transfer_amount');

        return compact('counts', 'monthlyTransferAmount');
    }

    /**
     * @return Collection<int, object{month: string, label: string, total: int}>
     */
    private function requestsPerMonth(): Collection
    {
        $start = now()->subMonths(11)->startOfMonth();

        $rows = PresenterRequest::query()
            ->where('status', '!=', PresenterRequestStatus::Draft)
            ->where('submitted_at', '>=', $start)
            ->get(['submitted_at'])
            ->groupBy(fn (PresenterRequest $request) => $request->submitted_at?->format('Y-m'))
            ->map(fn (Collection $group) => $group->count());

        $months = collect();
        for ($i = 0; $i < 12; $i++) {
            $date = $start->copy()->addMonths($i);
            $key = $date->format('Y-m');
            $months->push((object) [
                'month' => $key,
                'label' => $date->format('M Y'),
                'total' => (int) ($rows[$key] ?? 0),
            ]);
        }

        return $months;
    }

    /**
     * @return Collection<int, object{presenter_name: string, total_students: int}>
     */
    private function topPresentersByStudents(): Collection
    {
        return PresenterRequest::query()
            ->join('presenters', 'presenter_requests.presenter_id', '=', 'presenters.id')
            ->where('presenter_requests.status', '!=', PresenterRequestStatus::Draft)
            ->select('presenters.name as presenter_name', DB::raw('SUM(presenter_requests.total_students) as total_students'))
            ->groupBy('presenters.id', 'presenters.name')
            ->orderByDesc('total_students')
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, object{presenter_name: string, total_commission: float}>
     */
    private function topPresentersByCommission(): Collection
    {
        return PresenterRequest::query()
            ->join('presenters', 'presenter_requests.presenter_id', '=', 'presenters.id')
            ->where('presenter_requests.status', '!=', PresenterRequestStatus::Draft)
            ->select('presenters.name as presenter_name', DB::raw('SUM(presenter_requests.total_commission) as total_commission'))
            ->groupBy('presenters.id', 'presenters.name')
            ->orderByDesc('total_commission')
            ->limit(10)
            ->get();
    }
}
