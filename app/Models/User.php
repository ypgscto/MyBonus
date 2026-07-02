<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Presenter;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'bank_name',
        'account_number',
        'account_holder_name',
        'role',
        'status',
        'last_login_at',
        'must_change_password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function presenter(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Presenter::class);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Aktif;
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function hasCompleteBankAccount(): bool
    {
        if ($this->role !== UserRole::Keuangan) {
            return false;
        }

        return filled($this->bank_name)
            && filled($this->account_number)
            && filled($this->account_holder_name);
    }

    public function scopeKeuanganWithBankAccount($query)
    {
        return $query
            ->where('role', UserRole::Keuangan)
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->whereNotNull('account_number')
            ->where('account_number', '!=', '')
            ->whereNotNull('account_holder_name')
            ->where('account_holder_name', '!=', '');
    }
}
