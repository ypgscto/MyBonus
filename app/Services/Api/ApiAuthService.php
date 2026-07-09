<?php

namespace App\Services\Api;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApiAuthService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    /**
     * @return array{user: User, token: string, token_type: string, expires_at: ?string}
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $this->ensureIsNotRateLimited($email);

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            $this->hitRateLimit($email);

            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        if ($user->status !== UserStatus::Aktif) {
            $this->hitRateLimit($email);

            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Hubungi administrator.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($email));

        $user->update(['last_login_at' => now()]);
        $this->auditLog->logLogin($user);

        $deviceName = $deviceName ?: config('api.token.default_device_name', 'mobile');
        $abilities = config('api.token.abilities', ['mobile-access']);
        $expirationMinutes = config('api.token.expiration_minutes');

        $expiresAt = $expirationMinutes
            ? now()->addMinutes((int) $expirationMinutes)
            : null;

        $token = $user->createToken($deviceName, $abilities, $expiresAt)->plainTextToken;

        return [
            'user' => $user->fresh(['presenter.category']),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt?->toIso8601String(),
        ];
    }

    public function logout(User $user): void
    {
        $this->auditLog->logLogout($user);

        $token = $user->currentAccessToken();
        if ($token) {
            $user->tokens()->where('id', $token->id)->delete();
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(string $email): void
    {
        $maxAttempts = (int) config('api.rate_limit.login', 10);

        if (! RateLimiter::tooManyAttempts($this->throttleKey($email), $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($email));

        throw ValidationException::withMessages([
            'email' => [trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ])],
        ]);
    }

    private function hitRateLimit(string $email): void
    {
        RateLimiter::hit($this->throttleKey($email), 60);
    }

    private function throttleKey(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|api|'.request()->ip());
    }
}
