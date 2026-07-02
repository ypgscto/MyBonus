<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->applyFilters(AuditLog::query()->with('user'), $filters)
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        $modules = AuditLog::query()->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return [
            'users' => User::orderBy('name')->get(),
            'modules' => $modules,
            'actions' => $actions,
            'actionLabels' => collect(AuditAction::cases())->mapWithKeys(fn (AuditAction $a) => [$a->value => $a->label()]),
            'moduleLabels' => [
                'auth' => 'Autentikasi',
                'user' => 'User',
                'presenter_category' => 'Kategori Presenter',
                'presenter' => 'Presenter',
                'pmb_period' => 'Periode PMB',
                'commission_scheme' => 'Skema Komisi',
                'presenter_request' => 'Permintaan Presenter',
            ],
        ];
    }

    /**
     * @param  Builder<AuditLog>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        return $query;
    }
}
