<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresenterCategory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }

    public function presenters(): HasMany
    {
        return $this->hasMany(Presenter::class);
    }

    public function commissionSchemes(): HasMany
    {
        return $this->hasMany(CommissionScheme::class);
    }
}
