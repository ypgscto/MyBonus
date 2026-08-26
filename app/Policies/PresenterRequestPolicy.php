<?php

namespace App\Policies;

use App\Enums\PresenterRequestStatus;
use App\Enums\UserRole;
use App\Models\PresenterRequest;
use App\Models\User;

class PresenterRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::AdminPmb,
            UserRole::SuperAdmin,
            UserRole::Verifikator,
            UserRole::Keuangan,
        ], true);
    }

    public function view(User $user, PresenterRequest $presenterRequest): bool
    {
        return match ($user->role) {
            UserRole::SuperAdmin => true,
            UserRole::AdminPmb => $presenterRequest->created_by === $user->id,
            UserRole::Verifikator => $presenterRequest->status !== PresenterRequestStatus::Draft,
            UserRole::Keuangan => $presenterRequest->status !== PresenterRequestStatus::Draft
                && $presenterRequest->status !== PresenterRequestStatus::Submitted
                && $presenterRequest->status !== PresenterRequestStatus::RejectedByVerifikator
                && $presenterRequest->status !== PresenterRequestStatus::ApprovedByVerifikator,
            UserRole::Presenter => app(PresenterOwnsRequestPolicy::class)->view($user, $presenterRequest)
                && $presenterRequest->status !== PresenterRequestStatus::Draft,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::AdminPmb, UserRole::SuperAdmin], true);
    }

    public function update(User $user, PresenterRequest $presenterRequest): bool
    {
        if ($presenterRequest->status !== PresenterRequestStatus::Draft) {
            return false;
        }

        return match ($user->role) {
            UserRole::SuperAdmin => true,
            UserRole::AdminPmb => $presenterRequest->created_by === $user->id,
            default => false,
        };
    }

    public function submit(User $user, PresenterRequest $presenterRequest): bool
    {
        return $this->update($user, $presenterRequest);
    }

    public function manageDetails(User $user, PresenterRequest $presenterRequest): bool
    {
        return $this->update($user, $presenterRequest);
    }

    public function downloadPresenterTransferProof(User $user, PresenterRequest $presenterRequest): bool
    {
        if ($presenterRequest->presenterTransfer === null) {
            return false;
        }

        return match ($user->role) {
            UserRole::SuperAdmin, UserRole::Verifikator, UserRole::Keuangan => true,
            UserRole::AdminPmb => $presenterRequest->created_by === $user->id,
            default => false,
        };
    }

    public function downloadVerifikatorTransferProof(User $user, PresenterRequest $presenterRequest): bool
    {
        if ($presenterRequest->verifikatorTransfer === null) {
            return false;
        }

        return match ($user->role) {
            UserRole::SuperAdmin, UserRole::Verifikator, UserRole::Keuangan => true,
            UserRole::AdminPmb => $presenterRequest->created_by === $user->id,
            default => false,
        };
    }
}
