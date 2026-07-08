<?php

namespace App\Http\Requests\Master;

use App\Enums\RecordStatus;
use App\Rules\IndonesianPhoneNumber;
use App\Rules\PresenterEmailAvailable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePresenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $presenter = $this->route('presenter');

        return [
            'presenter_category_id' => [
                'required',
                Rule::exists('presenter_categories', 'id')->where('status', RecordStatus::Aktif->value),
            ],
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', new IndonesianPhoneNumber],
            'email' => [
                'required',
                'email',
                'max:255',
                new PresenterEmailAvailable($presenter?->id, $presenter?->user_id),
            ],
            'address' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(RecordStatus::class)],
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
            'name.required' => 'Nama presenter wajib diisi.',
            'bank_name.required' => 'Nama bank wajib diisi.',
            'account_number.required' => 'Nomor rekening wajib diisi.',
            'account_holder_name.required' => 'Atas nama rekening wajib diisi.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'email.required' => 'Email presenter wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh presenter atau user lain.',
        ];
    }
}
