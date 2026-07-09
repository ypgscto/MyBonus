<?php

namespace App\Http\Requests\Keuangan;

use App\Models\Presenter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransferToPresenterRequest extends FormRequest
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
        $request = $this->route('presenter_request') ?? $this->route('presenterRequest');
        $totalCommission = (float) ($request?->total_commission ?? 0);
        $transferAmount = (float) $this->input('transfer_amount', $totalCommission);

        return [
            'transfer_date' => ['required', 'date'],
            'transfer_amount' => ['required', 'numeric', 'min:0'],
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'finance_note' => ['nullable', 'string'],
            'note' => [
                Rule::requiredIf(abs($transferAmount - $totalCommission) > 0.009),
                'nullable',
                'string',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $presenter = $this->presenter();

            if (! $presenter) {
                $validator->errors()->add('transfer', 'Data presenter tidak ditemukan.');

                return;
            }

            if (! $presenter->hasCompleteBankAccount()) {
                $validator->errors()->add(
                    'transfer',
                    'Data rekening presenter belum lengkap di Master Presenter. Hubungi Admin PMB untuk melengkapi data bank.'
                );
            }
        });
    }

    public function presenter(): ?Presenter
    {
        $request = $this->route('presenter_request') ?? $this->route('presenterRequest');

        return $request?->presenter;
    }

    /**
     * @return array{bank: string, account_number: string, account_holder_name: string}
     */
    public function presenterBankDetails(): array
    {
        $presenter = $this->presenter();

        return [
            'bank' => $presenter->bank_name,
            'account_number' => $presenter->account_number,
            'account_holder_name' => $presenter->account_holder_name,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transfer_date.required' => 'Tanggal transfer wajib diisi.',
            'transfer_amount.required' => 'Nominal transfer wajib diisi.',
            'transfer_proof.required' => 'Bukti transfer wajib diupload.',
            'transfer_proof.mimes' => 'Bukti transfer harus berformat JPG, JPEG, PNG, atau PDF.',
            'transfer_proof.max' => 'Ukuran bukti transfer maksimal 5 MB.',
            'note.required' => 'Catatan alasan selisih wajib diisi jika nominal transfer berbeda dari total komisi.',
        ];
    }
}
