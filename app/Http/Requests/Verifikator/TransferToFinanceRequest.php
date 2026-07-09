<?php

namespace App\Http\Requests\Verifikator;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransferToFinanceRequest extends FormRequest
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
            'finance_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Keuangan->value)->where('status', UserStatus::Aktif->value),
            ],
            'transfer_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
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
            if ($validator->errors()->isNotEmpty() || ! $this->filled('finance_user_id')) {
                return;
            }

            $financeUser = User::query()->find($this->input('finance_user_id'));

            if (! $financeUser?->hasCompleteBankAccount()) {
                $validator->errors()->add(
                    'finance_user_id',
                    'User keuangan belum memiliki data rekening lengkap. Hubungi Super Admin untuk melengkapi master user.'
                );
            }
        });
    }

    public function financeUser(): User
    {
        return User::query()->findOrFail($this->validated('finance_user_id'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transfer_date.required' => 'Tanggal transfer wajib diisi.',
            'transfer_amount.required' => 'Nominal transfer wajib diisi.',
            'finance_user_id.required' => 'User keuangan penerima wajib dipilih.',
            'finance_user_id.exists' => 'User keuangan harus aktif.',
            'transfer_proof.required' => 'Bukti transfer wajib diupload.',
            'transfer_proof.mimes' => 'Bukti transfer harus berformat JPG, JPEG, PNG, atau PDF.',
            'transfer_proof.max' => 'Ukuran bukti transfer maksimal 5 MB.',
            'note.required' => 'Catatan alasan selisih wajib diisi jika nominal transfer berbeda dari total komisi.',
        ];
    }
}
