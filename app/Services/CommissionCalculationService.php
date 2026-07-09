<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\CommissionScheme;
use App\Models\PresenterRequest;
use Illuminate\Validation\ValidationException;

class CommissionCalculationService
{
    /**
     * @return array{total_students: int, commission_per_student: string, total_commission: float}
     */
    public function calculate(PresenterRequest $request): array
    {
        $preview = $this->preview($request);

        if (! $preview['available']) {
            throw ValidationException::withMessages([
                'commission' => $preview['message'] ?? 'Skema komisi aktif tidak ditemukan untuk kategori presenter dan periode PMB ini.',
            ]);
        }

        return [
            'total_students' => $preview['total_students'],
            'commission_per_student' => (string) $preview['commission_per_student'],
            'total_commission' => $preview['total_commission'],
        ];
    }

    /**
     * Estimasi komisi untuk draft (tidak melempar exception).
     *
     * @return array{
     *     available: bool,
     *     message: string|null,
     *     total_students: int,
     *     commission_per_student: float|null,
     *     total_commission: float,
     *     is_preview: true,
     *     presenter_category: string|null,
     *     pmb_period_label: string|null
     * }
     */
    public function preview(
        PresenterRequest $request,
        ?int $presenterId = null,
        ?int $pmbPeriodId = null,
    ): array {
        $request->loadMissing(['details', 'presenter.category', 'pmbPeriod']);

        $resolvedPresenterId = $presenterId ?? $request->presenter_id;
        $resolvedPeriodId = $pmbPeriodId ?? $request->pmb_period_id;

        $presenter = $resolvedPresenterId === $request->presenter_id
            ? $request->presenter
            : \App\Models\Presenter::with('category')->find($resolvedPresenterId);

        $pmbPeriod = $resolvedPeriodId === $request->pmb_period_id
            ? $request->pmbPeriod
            : \App\Models\PmbPeriod::find($resolvedPeriodId);

        $totalStudents = $request->details->count();

        $scheme = CommissionScheme::query()
            ->where('presenter_category_id', $presenter?->presenter_category_id)
            ->where('pmb_period_id', $resolvedPeriodId)
            ->where('status', RecordStatus::Aktif)
            ->first();

        if (! $scheme) {
            return [
                'available' => false,
                'message' => 'Skema komisi aktif tidak ditemukan untuk kategori presenter dan periode PMB ini.',
                'total_students' => $totalStudents,
                'commission_per_student' => null,
                'total_commission' => 0.0,
                'is_preview' => true,
                'presenter_category' => $presenter?->category?->name,
                'pmb_period_label' => $pmbPeriod
                    ? $pmbPeriod->academic_year.' – '.$pmbPeriod->wave
                    : null,
            ];
        }

        $commissionPerStudent = (float) $scheme->commission_amount_per_student;

        return [
            'available' => true,
            'message' => null,
            'total_students' => $totalStudents,
            'commission_per_student' => $commissionPerStudent,
            'total_commission' => $totalStudents * $commissionPerStudent,
            'is_preview' => true,
            'presenter_category' => $presenter?->category?->name,
            'pmb_period_label' => $pmbPeriod
                ? $pmbPeriod->academic_year.' – '.$pmbPeriod->wave
                : null,
        ];
    }
}
