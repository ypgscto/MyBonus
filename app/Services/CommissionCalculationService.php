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
        $request->loadMissing(['details', 'presenter', 'pmbPeriod']);

        $scheme = CommissionScheme::query()
            ->where('presenter_category_id', $request->presenter?->presenter_category_id)
            ->where('pmb_period_id', $request->pmb_period_id)
            ->where('status', RecordStatus::Aktif)
            ->first();

        if (! $scheme) {
            throw ValidationException::withMessages([
                'commission' => 'Skema komisi aktif tidak ditemukan untuk kategori presenter dan periode PMB ini.',
            ]);
        }

        $totalStudents = $request->details->count();
        $commissionPerStudent = $scheme->commission_amount_per_student;
        $totalCommission = $totalStudents * (float) $commissionPerStudent;

        return [
            'total_students' => $totalStudents,
            'commission_per_student' => $commissionPerStudent,
            'total_commission' => $totalCommission,
        ];
    }
}
