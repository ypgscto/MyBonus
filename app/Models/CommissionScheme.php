<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionScheme extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'presenter_category_id',
        'pmb_period_id',
        'commission_amount_per_student',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_amount_per_student' => 'decimal:2',
            'status' => RecordStatus::class,
        ];
    }

    public function presenterCategory(): BelongsTo
    {
        return $this->belongsTo(PresenterCategory::class);
    }

    public function pmbPeriod(): BelongsTo
    {
        return $this->belongsTo(PmbPeriod::class);
    }
}
