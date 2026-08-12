<?php

namespace App\Http\Requests\Admin;

use App\Enums\NotificationStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationLogRequest extends FormRequest
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'provider' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::enum(NotificationStatus::class)],
            'recipient_role' => ['nullable', 'string', 'max:50'],
            'request_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
