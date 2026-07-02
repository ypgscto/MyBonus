<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presenter extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'presenter_category_id',
        'user_id',
        'name',
        'bank_name',
        'account_number',
        'account_holder_name',
        'phone',
        'email',
        'address',
        'note',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'account_created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forAuthenticatedUser(): ?self
    {
        return static::query()->where('user_id', auth()->id())->first();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PresenterCategory::class, 'presenter_category_id');
    }

    public function presenterRequests(): HasMany
    {
        return $this->hasMany(PresenterRequest::class);
    }

    public function presenterTransfers(): HasMany
    {
        return $this->hasMany(PresenterTransfer::class);
    }

    public function hasCompleteBankAccount(): bool
    {
        return filled($this->bank_name)
            && filled($this->account_number)
            && filled($this->account_holder_name);
    }
}
