<?php

namespace App\Services;

use App\Enums\PresenterRequestStatus;
use App\Models\Presenter;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PresenterTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PresenterPortalService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(Presenter $presenter): array
    {
        $base = $this->requestQuery($presenter)->where('status', '!=', PresenterRequestStatus::Draft);

        $totalStudents = (int) (clone $base)->sum('total_students');
        $totalRequests = (clone $base)->count();
        $totalCommission = (float) (clone $base)->sum('total_commission');

        $paidStatuses = [
            PresenterRequestStatus::TransferredToPresenter,
            PresenterRequestStatus::Closed,
        ];

        $paidCommission = (float) (clone $base)
            ->whereIn('status', $paidStatuses)
            ->sum('total_commission');

        $pendingCommission = $totalCommission - $paidCommission;

        $latestPayout = PresenterTransfer::query()
            ->where('presenter_id', $presenter->id)
            ->latest('transfer_date')
            ->first();

        $recentStudents = PresenterRequestDetail::query()
            ->with(['presenterRequest.pmbPeriod'])
            ->whereHas('presenterRequest', fn (Builder $q) => $this->scopePresenter($q, $presenter))
            ->whereHas('presenterRequest', fn (Builder $q) => $q->where('status', '!=', PresenterRequestStatus::Draft))
            ->latest('id')
            ->limit(5)
            ->get();

        $recentRequests = (clone $base)
            ->with(['pmbPeriod', 'presenterTransfer'])
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return compact(
            'totalStudents',
            'totalRequests',
            'totalCommission',
            'paidCommission',
            'pendingCommission',
            'latestPayout',
            'recentStudents',
            'recentRequests',
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function students(Presenter $presenter, array $filters = []): LengthAwarePaginator
    {
        return PresenterRequestDetail::query()
            ->with(['presenterRequest.pmbPeriod', 'presenterRequest.presenter'])
            ->whereHas('presenterRequest', function (Builder $query) use ($presenter, $filters) {
                $this->scopePresenter($query, $presenter);
                $query->where('status', '!=', PresenterRequestStatus::Draft);
                $this->applyRequestFilters($query, $filters);
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('nim', 'like', "%{$search}%")
                        ->orWhere('student_name', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function requests(Presenter $presenter, array $filters = []): LengthAwarePaginator
    {
        return $this->requestQuery($presenter)
            ->with(['pmbPeriod', 'presenterTransfer'])
            ->where('status', '!=', PresenterRequestStatus::Draft)
            ->when(true, fn (Builder $query) => $this->applyRequestFilters($query, $filters))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function payouts(Presenter $presenter, array $filters = []): LengthAwarePaginator
    {
        return $this->requestQuery($presenter)
            ->with(['pmbPeriod', 'presenterTransfer'])
            ->where('status', '!=', PresenterRequestStatus::Draft)
            ->when($filters['payout_status'] ?? null, function (Builder $query, string $status) {
                match ($status) {
                    'pending' => $query->whereIn('status', [PresenterRequestStatus::Submitted, PresenterRequestStatus::Draft]),
                    'verification' => $query->where('status', PresenterRequestStatus::Submitted),
                    'approved' => $query->where('status', PresenterRequestStatus::ApprovedByVerifikator),
                    'finance' => $query->whereIn('status', [PresenterRequestStatus::TransferredToFinance, PresenterRequestStatus::ReceivedByFinance]),
                    'transferred' => $query->where('status', PresenterRequestStatus::TransferredToPresenter),
                    'closed' => $query->where('status', PresenterRequestStatus::Closed),
                    'rejected' => $query->whereIn('status', [PresenterRequestStatus::RejectedByVerifikator, PresenterRequestStatus::Cancelled]),
                    default => null,
                };
            })
            ->when($filters['transfer_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('transferred_to_presenter_at', '>=', $date))
            ->when($filters['transfer_to'] ?? null, fn (Builder $q, $date) => $q->whereDate('transferred_to_presenter_at', '<=', $date))
            ->when(true, fn (Builder $query) => $this->applyRequestFilters($query, $filters))
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function findOwnedRequest(Presenter $presenter, int $requestId): PresenterRequest
    {
        return $this->requestQuery($presenter)
            ->with(['pmbPeriod', 'presenter', 'details', 'presenterTransfer', 'presenter.category'])
            ->where('status', '!=', PresenterRequestStatus::Draft)
            ->findOrFail($requestId);
    }

    /**
     * @return Collection<int, \App\Models\PmbPeriod>
     */
    public function periodOptions(Presenter $presenter): Collection
    {
        return PresenterRequest::query()
            ->where('presenter_id', $presenter->id)
            ->where('status', '!=', PresenterRequestStatus::Draft)
            ->with('pmbPeriod')
            ->get()
            ->pluck('pmbPeriod')
            ->filter()
            ->unique('id')
            ->sortByDesc('start_date')
            ->values();
    }

    private function requestQuery(Presenter $presenter): Builder
    {
        return PresenterRequest::query()->where('presenter_id', $presenter->id);
    }

    private function scopePresenter(Builder $query, Presenter $presenter): void
    {
        $query->where('presenter_id', $presenter->id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyRequestFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['pmb_period_id'])) {
            $query->where('pmb_period_id', $filters['pmb_period_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('request_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('request_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('request_code', 'like', "%{$search}%");
        }
    }
}
