<?php

namespace App\Http\Resources;

use App\Models\PresenterRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PresenterRequest */
class PresenterRequestResource extends JsonResource
{
    public function __construct(
        $resource,
        private bool $includeBankNote = false,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $commissionPreview = $this->isEditable()
            ? app(\App\Services\CommissionCalculationService::class)->preview($this->resource)
            : null;

        $usePreview = $this->isEditable();

        $totalCommission = $usePreview
            ? (float) ($commissionPreview['total_commission'] ?? 0.0)
            : (float) ($this->total_commission ?? 0.0);

        $commissionPerStudent = $usePreview
            ? $commissionPreview['commission_per_student']
            : ($this->commission_per_student !== null ? (float) $this->commission_per_student : null);

        return [
            'id' => $this->id,
            'request_code' => $this->request_code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'presenter_status_label' => $this->status->presenterLabel(),
            'payout_status_label' => $this->status->payoutLabel(),
            'request_date' => $this->request_date?->toDateString(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'transferred_to_finance_at' => $this->transferred_to_finance_at?->toIso8601String(),
            'received_by_finance_at' => $this->received_by_finance_at?->toIso8601String(),
            'transferred_to_presenter_at' => $this->transferred_to_presenter_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'admin_note' => $this->admin_note,
            'verifikator_note' => $this->verifikator_note,
            'finance_note' => $this->finance_note,
            'total_students' => $this->isEditable()
                ? ($commissionPreview['total_students'] ?? (int) ($this->details_count ?? $this->details->count()))
                : (int) $this->total_students,
            'commission_per_student' => $commissionPerStudent,
            'total_commission' => $totalCommission,
            'commission_is_preview' => $usePreview,
            'commission_preview' => $this->when($commissionPreview !== null, $commissionPreview),
            'is_editable' => $this->isEditable(),
            'bank_transfer_note' => $this->when($this->includeBankNote, fn () => $this->bankTransferNote()),
            'presenter' => $this->whenLoaded('presenter', fn () => new PresenterResource($this->presenter)),
            'pmb_period' => $this->whenLoaded('pmbPeriod', fn () => [
                'id' => $this->pmbPeriod->id,
                'academic_year' => $this->pmbPeriod->academic_year,
                'wave' => $this->pmbPeriod->wave,
                'label' => $this->pmbPeriod->academic_year.' – '.$this->pmbPeriod->wave,
            ]),
            'details' => PresenterRequestDetailResource::collection($this->whenLoaded('details')),
            'details_count' => $this->when(isset($this->details_count), $this->details_count),
            'verifikator_transfer' => $this->whenLoaded(
                'verifikatorTransfer',
                fn () => new VerifikatorTransferResource($this->verifikatorTransfer)
            ),
            'presenter_transfer' => $this->whenLoaded(
                'presenterTransfer',
                fn () => new PresenterTransferResource($this->presenterTransfer)
            ),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
        ];
    }
}
