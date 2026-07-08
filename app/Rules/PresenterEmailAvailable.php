<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\Presenter;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PresenterEmailAvailable implements ValidationRule
{
    public function __construct(
        private ?int $ignorePresenterId = null,
        private ?int $ignoreUserId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));

        $presenterQuery = Presenter::query()->where('email', $email);
        if ($this->ignorePresenterId) {
            $presenterQuery->where('id', '!=', $this->ignorePresenterId);
        }
        if ($presenterQuery->exists()) {
            $fail('Email sudah digunakan oleh presenter lain.');

            return;
        }

        $userQuery = User::query()->where('email', $email);
        if ($this->ignoreUserId) {
            $userQuery->where('id', '!=', $this->ignoreUserId);
        }

        $user = $userQuery->first();
        if (! $user) {
            return;
        }

        if ($user->role === UserRole::Presenter && ! $user->presenter) {
            return;
        }

        $fail('Email sudah digunakan oleh user '.$user->role->label().'. Gunakan email lain.');
    }
}
