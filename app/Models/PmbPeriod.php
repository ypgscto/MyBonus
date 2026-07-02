<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PmbPeriod extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'academic_year',
        'wave',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => RecordStatus::class,
        ];
    }

    public function commissionSchemes(): HasMany
    {
        return $this->hasMany(CommissionScheme::class);
    }

    public function presenterRequests(): HasMany
    {
        return $this->hasMany(PresenterRequest::class);
    }
}
