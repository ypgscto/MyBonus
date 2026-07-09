<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PresenterRequestStatus;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\PmbPeriod;
use App\Models\Presenter;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function requestStatuses(): JsonResponse
    {
        $statuses = collect(PresenterRequestStatus::cases())->map(fn (PresenterRequestStatus $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'presenter_label' => $status->presenterLabel(),
            'payout_label' => $status->payoutLabel(),
        ]);

        return ApiResponse::success($statuses);
    }

    public function pmbPeriods(): JsonResponse
    {
        $periods = PmbPeriod::query()
            ->where('status', RecordStatus::Aktif)
            ->orderByDesc('start_date')
            ->get(['id', 'academic_year', 'wave', 'start_date', 'end_date'])
            ->map(fn (PmbPeriod $period) => [
                'id' => $period->id,
                'academic_year' => $period->academic_year,
                'wave' => $period->wave,
                'label' => $period->academic_year.' – '.$period->wave,
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
            ]);

        return ApiResponse::success($periods);
    }

    public function presenters(): JsonResponse
    {
        $presenters = Presenter::query()
            ->with('category:id,name')
            ->where('status', RecordStatus::Aktif)
            ->orderBy('name')
            ->get(['id', 'name', 'presenter_category_id', 'bank_name', 'account_number', 'account_holder_name', 'phone', 'email']);

        return ApiResponse::success($presenters);
    }
}
