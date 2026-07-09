<?php

namespace App\Http\Resources;

use App\Models\PresenterRequestDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PresenterRequestDetail */
class PresenterRequestDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nim' => $this->nim,
            'student_name' => $this->student_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_proof_file' => $this->payment_proof_file,
            'has_payment_proof' => filled($this->payment_proof_file),
            'note' => $this->note,
        ];
    }
}
