<?php

namespace App\Http\Requests\Report;

use App\Enums\PresenterRequestStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, [UserRole::SuperAdmin, UserRole::AdminPmb], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ReportType::class)],
            'pmb_period_id' => ['nullable', 'exists:pmb_periods,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'presenter_id' => ['nullable', 'exists:presenters,id'],
            'presenter_category_id' => ['nullable', 'exists:presenter_categories,id'],
            'status' => ['nullable', Rule::enum(PresenterRequestStatus::class)],
            'created_by' => ['nullable', 'exists:users,id'],
            'verifikator_id' => ['nullable', 'exists:users,id'],
            'keuangan_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
