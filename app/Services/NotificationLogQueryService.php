<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class NotificationLogQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->applyFilters(
            NotificationLog::query()->with('presenterRequest'),
            $filters
        )->latest('created_at')->paginate(25)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        return [
            'providers' => NotificationLog::query()->distinct()->orderBy('provider')->pluck('provider')->filter(),
            'statuses' => NotificationStatus::cases(),
            'recipientRoles' => NotificationLog::query()->distinct()->orderBy('recipient_role')->pluck('recipient_role')->filter(),
        ];
    }

    /**
     * @param  Builder<NotificationLog>  $query
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

        if (! empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['recipient_role'])) {
            $query->where('recipient_role', $filters['recipient_role']);
        }

        if (! empty($filters['request_code'])) {
            $code = $filters['request_code'];
            $query->whereHas('presenterRequest', fn (Builder $q) => $q->where('request_code', 'like', "%{$code}%"));
        }

        return $query;
    }
}
