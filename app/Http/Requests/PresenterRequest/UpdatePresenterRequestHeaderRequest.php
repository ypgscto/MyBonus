<?php

namespace App\Http\Requests\PresenterRequest;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePresenterRequestHeaderRequest extends FormRequest
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
            'presenter_id' => [
                'required',
                Rule::exists('presenters', 'id')->where('status', RecordStatus::Aktif->value),
            ],
            'admin_note' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pmb_period_id.required' => 'Periode PMB wajib dipilih.',
            'pmb_period_id.exists' => 'Periode PMB harus aktif.',
            'presenter_id.required' => 'Presenter wajib dipilih.',
            'presenter_id.exists' => 'Presenter harus aktif.',
        ];
    }
}
