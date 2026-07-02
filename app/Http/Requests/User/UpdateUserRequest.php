<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Rules\IndonesianPhoneNumber;
use App\Support\WhatsappNumberHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SuperAdmin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $isKeuangan = $this->input('role') === UserRole::Keuangan->value;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['required', 'string', 'max:20', new IndonesianPhoneNumber],
            'role' => ['required', Rule::enum(UserRole::class)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'bank_name' => [Rule::requiredIf($isKeuangan), 'nullable', 'string', 'max:255'],
            'account_number' => [Rule::requiredIf($isKeuangan), 'nullable', 'string', 'max:50'],
            'account_holder_name' => [Rule::requiredIf($isKeuangan), 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'role.required' => 'Role wajib dipilih.',
            'status.required' => 'Status wajib dipilih.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'bank_name.required' => 'Nama bank wajib diisi untuk user Keuangan.',
            'account_number.required' => 'Nomor rekening wajib diisi untuk user Keuangan.',
            'account_holder_name.required' => 'Atas nama rekening wajib diisi untuk user Keuangan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => WhatsappNumberHelper::normalize((string) $this->input('phone')),
            ]);
        }
    }
}
