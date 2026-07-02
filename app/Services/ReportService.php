<?php

namespace App\Services;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Presenter;
use App\Models\PresenterCategory;
use App\Models\PresenterRequest;
use App\Models\PresenterRequestDetail;
use App\Models\PmbPeriod;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
  /**
   * @return array<string, mixed>
   */
    public function filterOptions(User $user): array
    {
        $isAdminPmb = $user->role === UserRole::AdminPmb;

        return [
            'pmbPeriods' => PmbPeriod::orderByDesc('start_date')->get(),
            'presenters' => Presenter::orderBy('name')->get(),
            'categories' => PresenterCategory::orderBy('name')->get(),
            'statuses' => PresenterRequestStatus::cases(),
            'admins' => $isAdminPmb
                ? User::where('id', $user->id)->get()
                : User::where('role', UserRole::AdminPmb)->orderBy('name')->get(),
            'verifikators' => User::where('role', UserRole::Verifikator)->orderBy('name')->get(),
            'keuanganUsers' => User::where('role', UserRole::Keuangan)->orderBy('name')->get(),
            'reportTypes' => ReportType::cases(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{title: string, rows: LengthAwarePaginator|Collection<int, mixed>, type: ReportType}
     */
    public function generate(ReportType $type, array $filters, User $user): array
    {
        $rows = match ($type) {
            ReportType::PresenterRequests => $this->presenterRequests($filters, $user),
            ReportType::StudentDetails => $this->studentDetails($filters, $user),
            ReportType::DuplicateNim => $this->duplicateNim($filters, $user),
            ReportType::VerifikatorTransfers => $this->verifikatorTransfers($filters, $user),
            ReportType::PresenterTransfers => $this->presenterTransfers($filters, $user),
            ReportType::TransferVariance => $this->transferVariance($filters, $user),
            ReportType::Rejected => $this->rejectedRequests($filters, $user),
            ReportType::Closed => $this->closedRequests($filters, $user),
            ReportType::ActivePresenters => $this->activePresenters($filters, $user),
            ReportType::AuditActivity => $this->auditActivity($filters, $user),
        };

        return [
            'title' => $type->label(),
            'type' => $type,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function presenterRequests(array $filters, User $user): LengthAwarePaginator
    {
        return $this->applyRequestFilters(
            PresenterRequest::query()
                ->with(['presenter.category', 'pmbPeriod', 'creator', 'approver'])
                ->where('status', '!=', PresenterRequestStatus::Draft),
            $filters,
            $user
        )->latest('request_date')->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function studentDetails(array $filters, User $user): LengthAwarePaginator
    {
        return PresenterRequestDetail::query()
            ->with(['presenterRequest.presenter.category', 'presenterRequest.pmbPeriod'])
            ->whereHas('presenterRequest', function (Builder $query) use ($filters, $user) {
                $this->applyRequestFilters($query->where('status', '!=', PresenterRequestStatus::Draft), $filters, $user);
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function duplicateNim(array $filters, User $user): LengthAwarePaginator
    {
        $duplicateNims = PresenterRequestDetail::query()
            ->select('nim')
            ->whereHas('presenterRequest', fn (Builder $q) => $this->applyRequestFilters($q, $filters, $user))
            ->groupBy('nim')
            ->havingRaw('COUNT(DISTINCT presenter_request_id) > 1')
            ->pluck('nim');

        return PresenterRequestDetail::query()
            ->with(['presenterRequest.presenter', 'presenterRequest.pmbPeriod'])
            ->whereIn('nim', $duplicateNims)
            ->whereHas('presenterRequest', fn (Builder $q) => $this->applyRequestFilters($q, $filters, $user))
            ->orderBy('nim')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function verifikatorTransfers(array $filters, User $user): LengthAwarePaginator
    {
        return $this->applyRequestFilters(
            PresenterRequest::query()
                ->with(['presenter', 'pmbPeriod', 'verifikatorTransfer.transferrer', 'verifikatorTransfer.financeUser'])
                ->whereHas('verifikatorTransfer'),
            $filters,
            $user
        )->latest('transferred_to_finance_at')->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function presenterTransfers(array $filters, User $user): LengthAwarePaginator
    {
        return $this->applyRequestFilters(
            PresenterRequest::query()
                ->with(['presenter', 'pmbPeriod', 'presenterTransfer.transferrer'])
                ->whereHas('presenterTransfer'),
            $filters,
            $user
        )->latest('transferred_to_presenter_at')->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function transferVariance(array $filters, User $user): LengthAwarePaginator
    {
        return $this->applyRequestFilters(
            PresenterRequest::query()
                ->with(['presenter', 'pmbPeriod', 'verifikatorTransfer', 'presenterTransfer'])
                ->where(function (Builder $query) {
                    $query->whereHas('verifikatorTransfer')
                        ->orWhereHas('presenterTransfer');
                }),
            $filters,
            $user
        )->latest('request_date')->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function rejectedRequests(array $filters, User $user): LengthAwarePaginator
    {
        $filters['status'] = PresenterRequestStatus::RejectedByVerifikator->value;

        return $this->applyRequestFilters(
            PresenterRequest::query()
                ->with(['presenter', 'pmbPeriod', 'creator', 'rejector']),
            $filters,
            $user
        )->latest('rejected_at')->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function closedRequests(array $filters, User $user): LengthAwarePaginator
    {
        $filters['status'] = PresenterRequestStatus::Closed->value;

        return $this->applyRequestFilters(
            PresenterRequest::query()
                ->with(['presenter', 'pmbPeriod', 'creator', 'closer']),
            $filters,
            $user
        )->latest('closed_at')->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function activePresenters(array $filters, User $user): LengthAwarePaginator
    {
        $query = PresenterRequest::query()
            ->join('presenters', 'presenter_requests.presenter_id', '=', 'presenters.id')
            ->where('presenter_requests.status', '!=', PresenterRequestStatus::Draft)
            ->select(
                'presenters.id as presenter_id',
                'presenters.name as presenter_name',
                'presenters.phone as presenter_phone',
                DB::raw('COUNT(presenter_requests.id) as total_requests'),
                DB::raw('SUM(presenter_requests.total_students) as total_students'),
                DB::raw('SUM(presenter_requests.total_commission) as total_commission')
            )
            ->groupBy('presenters.id', 'presenters.name', 'presenters.phone');

        $this->applyRequestFiltersOnJoined($query, $filters, $user);

        return $query->orderByDesc('total_students')->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function auditActivity(array $filters, User $user): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user')->latest('created_at');

        if ($user->role === UserRole::AdminPmb) {
            $requestIds = PresenterRequest::where('created_by', $user->id)->pluck('id');
            $query->where(function (Builder $q) use ($user, $requestIds) {
                $q->where('user_id', $user->id)
                    ->orWhere(function (Builder $inner) use ($requestIds) {
                        $inner->where('module', 'presenter_request')
                            ->whereIn('reference_id', $requestIds);
                    });
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('user_id', $filters['created_by']);
        }

        return $query->paginate(25)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyRequestFilters(Builder $query, array $filters, User $user): Builder
    {
        if ($user->role === UserRole::AdminPmb) {
            $query->where('created_by', $user->id);
        }

        if (! empty($filters['pmb_period_id'])) {
            $query->where('pmb_period_id', $filters['pmb_period_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('request_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('request_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['presenter_id'])) {
            $query->where('presenter_id', $filters['presenter_id']);
        }

        if (! empty($filters['presenter_category_id'])) {
            $query->whereHas('presenter', fn (Builder $q) => $q->where('presenter_category_id', $filters['presenter_category_id']));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['created_by']) && $user->role === UserRole::SuperAdmin) {
            $query->where('created_by', $filters['created_by']);
        }

        if (! empty($filters['verifikator_id'])) {
            $verifikatorId = $filters['verifikator_id'];
            $query->where(function (Builder $q) use ($verifikatorId) {
                $q->where('approved_by', $verifikatorId)
                    ->orWhere('rejected_by', $verifikatorId)
                    ->orWhere('transferred_to_finance_by', $verifikatorId);
            });
        }

        if (! empty($filters['keuangan_id'])) {
            $keuanganId = $filters['keuangan_id'];
            $query->where(function (Builder $q) use ($keuanganId) {
                $q->where('received_by_finance_by', $keuanganId)
                    ->orWhere('transferred_to_presenter_by', $keuanganId)
                    ->orWhereHas('verifikatorTransfer', fn (Builder $vt) => $vt->where('finance_user_id', $keuanganId));
            });
        }

        return $query;
    }

    /**
     * @param  Builder<\Illuminate\Database\Query\Builder>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyRequestFiltersOnJoined(Builder $query, array $filters, User $user): void
    {
        if ($user->role === UserRole::AdminPmb) {
            $query->where('presenter_requests.created_by', $user->id);
        }

        if (! empty($filters['pmb_period_id'])) {
            $query->where('presenter_requests.pmb_period_id', $filters['pmb_period_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('presenter_requests.request_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('presenter_requests.request_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['presenter_id'])) {
            $query->where('presenter_requests.presenter_id', $filters['presenter_id']);
        }

        if (! empty($filters['presenter_category_id'])) {
            $query->where('presenters.presenter_category_id', $filters['presenter_category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('presenter_requests.status', $filters['status']);
        }
    }

    public static function hasAmountVariance(float $expected, ?float $actual): bool
    {
        if ($actual === null) {
            return false;
        }

        return abs($expected - $actual) > 0.009;
    }

    /**
     * @return array{verifikator: bool, presenter: bool, any: bool}
     */
    public static function varianceFlags(PresenterRequest $request): array
    {
        $commission = (float) $request->total_commission;
        $verifikatorAmount = $request->verifikatorTransfer ? (float) $request->verifikatorTransfer->transfer_amount : null;
        $presenterAmount = $request->presenterTransfer ? (float) $request->presenterTransfer->transfer_amount : null;

        $verifikator = self::hasAmountVariance($commission, $verifikatorAmount);
        $presenter = self::hasAmountVariance($commission, $presenterAmount);

        return [
            'verifikator' => $verifikator,
            'presenter' => $presenter,
            'any' => $verifikator || $presenter,
        ];
    }
}
