<?php

namespace App\Http\Requests\Master;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommissionSchemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pmb_period_id' => [
                'required',
                Rule::exists('pmb_periods', 'id')->where('status', RecordStatus::Aktif->value),
            ],
            'commission_amount_per_student' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
            'presenter_category_id' => [
                'required',
                Rule::exists('presenter_categories', 'id')->where('status', RecordStatus::Aktif->value),
                Rule::unique('commission_schemes')
                    ->where(fn ($query) => $query->where('pmb_period_id', $this->pmb_period_id)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'presenter_category_id.required' => 'Kategori presenter wajib dipilih.',
            'presenter_category_id.exists' => 'Kategori presenter harus aktif.',
            'pmb_period_id.required' => 'Periode PMB wajib dipilih.',
            'pmb_period_id.exists' => 'Periode PMB harus aktif.',
            'commission_amount_per_student.required' => 'Nominal komisi wajib diisi.',
            'commission_amount_per_student.numeric' => 'Nominal komisi harus berupa angka.',
            'commission_amount_per_student.min' => 'Nominal komisi tidak boleh minus.',
        ];
    }
}
