<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'presenter_request_id',
        'recipient_role',
        'recipient_name',
        'recipient_phone',
        'message',
        'provider',
        'provider_response',
        'status',
        'sent_at',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function presenterRequest(): BelongsTo
    {
        return $this->belongsTo(PresenterRequest::class);
    }
}
