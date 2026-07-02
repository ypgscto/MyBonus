<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenterTransfer extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'presenter_request_id',
        'transferred_by',
        'transfer_date',
        'transfer_amount',
        'presenter_id',
        'destination_bank',
        'destination_account_number',
        'destination_account_name',
        'transfer_proof_file',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'transfer_amount' => 'decimal:2',
        ];
    }

    public function presenterRequest(): BelongsTo
    {
        return $this->belongsTo(PresenterRequest::class);
    }

    public function transferrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(Presenter::class);
    }
}
