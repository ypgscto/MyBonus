<?php

namespace App\Models;

use App\Enums\PresenterRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PresenterRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'request_code',
        'pmb_period_id',
        'presenter_id',
        'created_by',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'transferred_to_finance_by',
        'received_by_finance_by',
        'transferred_to_presenter_by',
        'closed_by',
        'status',
        'request_date',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'transferred_to_finance_at',
        'received_by_finance_at',
        'transferred_to_presenter_at',
        'closed_at',
        'rejection_reason',
        'admin_note',
        'verifikator_note',
        'finance_note',
        'total_students',
        'commission_per_student',
        'total_commission',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PresenterRequestStatus::class,
            'request_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'transferred_to_finance_at' => 'datetime',
            'received_by_finance_at' => 'datetime',
            'transferred_to_presenter_at' => 'datetime',
            'closed_at' => 'datetime',
            'commission_per_student' => 'decimal:2',
            'total_commission' => 'decimal:2',
        ];
    }

    public function pmbPeriod(): BelongsTo
    {
        return $this->belongsTo(PmbPeriod::class);
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(Presenter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function financeTransferrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_to_finance_by');
    }

    public function financeReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_finance_by');
    }

    public function presenterTransferrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_to_presenter_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PresenterRequestDetail::class);
    }

    public function verifikatorTransfer(): HasOne
    {
        return $this->hasOne(VerifikatorTransfer::class);
    }

    public function presenterTransfer(): HasOne
    {
        return $this->hasOne(PresenterTransfer::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isEditable(): bool
    {
        return $this->status === PresenterRequestStatus::Draft;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', PresenterRequestStatus::Draft);
    }

    public function scopeHistory($query)
    {
        return $query->where('status', '!=', PresenterRequestStatus::Draft);
    }

    public function bankTransferNote(): string
    {
        $nims = $this->relationLoaded('details')
            ? $this->details->sortBy('id')->pluck('nim')
            : $this->details()->orderBy('id')->pluck('nim');

        return $this->request_code.' : '.$nims->filter()->implode(', ');
    }

    public function paymentDate(): ?\Illuminate\Support\Carbon
    {
        $details = $this->relationLoaded('details')
            ? $this->details->sortBy('id')
            : $this->details()->orderBy('id')->get();

        return $details->first(fn ($detail) => $detail->payment_date !== null)?->payment_date;
    }
}
