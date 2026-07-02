<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresenterRequestDetail extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'presenter_request_id',
        'nim',
        'student_name',
        'birth_date',
        'payment_date',
        'payment_proof_file',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function presenterRequest(): BelongsTo
    {
        return $this->belongsTo(PresenterRequest::class);
    }
}
