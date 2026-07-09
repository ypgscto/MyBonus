<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'status' => $this->status->value,
            'must_change_password' => $this->must_change_password,
            'bank_name' => $this->when(
                $this->role->value === 'keuangan',
                $this->bank_name
            ),
            'account_number' => $this->when(
                $this->role->value === 'keuangan',
                $this->account_number
            ),
            'account_holder_name' => $this->when(
                $this->role->value === 'keuangan',
                $this->account_holder_name
            ),
            'presenter' => $this->whenLoaded('presenter', fn () => new PresenterResource($this->presenter)),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
        ];
    }
}
