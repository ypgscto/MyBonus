<?php

namespace App\Http\Resources;

use App\Models\VerifikatorTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VerifikatorTransfer */
class VerifikatorTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_date' => $this->transfer_date?->toDateString(),
            'transfer_amount' => (float) $this->transfer_amount,
            'destination_bank' => $this->destination_bank,
            'destination_account_number' => $this->destination_account_number,
            'destination_account_name' => $this->destination_account_name,
            'has_transfer_proof' => filled($this->transfer_proof_file),
            'note' => $this->note,
            'finance_user' => $this->whenLoaded('financeUser', fn () => [
                'id' => $this->financeUser->id,
                'name' => $this->financeUser->name,
            ]),
            'transferred_by' => $this->whenLoaded('transferrer', fn () => [
                'id' => $this->transferrer->id,
                'name' => $this->transferrer->name,
            ]),
        ];
    }
}
