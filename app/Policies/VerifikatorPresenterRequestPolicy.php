<?php

namespace App\Policies;

use App\Enums\PresenterRequestStatus;
use App\Enums\UserRole;
use App\Models\PresenterRequest;
use App\Models\User;

class VerifikatorPresenterRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Verifikator;
    }

    public function view(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Verifikator;
    }

    public function reject(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Verifikator
            && $presenterRequest->status === PresenterRequestStatus::Submitted;
    }

    public function approve(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Verifikator
            && $presenterRequest->status === PresenterRequestStatus::Submitted;
    }

    public function transfer(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Verifikator
            && $presenterRequest->status === PresenterRequestStatus::ApprovedByVerifikator;
    }

    public function downloadPaymentProof(User $user, PresenterRequest $presenterRequest): bool
    {
        if ($presenterRequest->status === PresenterRequestStatus::Draft) {
            return false;
        }

        return match ($user->role) {
            UserRole::AdminPmb => $presenterRequest->created_by === $user->id,
            UserRole::Verifikator => true,
            UserRole::Keuangan => in_array($presenterRequest->status, [
                PresenterRequestStatus::TransferredToFinance,
                PresenterRequestStatus::ReceivedByFinance,
                PresenterRequestStatus::TransferredToPresenter,
                PresenterRequestStatus::Closed,
            ], true),
            UserRole::SuperAdmin => true,
            default => false,
        };
    }

    public function downloadVerifikatorTransferProof(User $user, PresenterRequest $presenterRequest): bool
    {
        return in_array($user->role, [UserRole::Verifikator, UserRole::Keuangan, UserRole::SuperAdmin], true)
            && $presenterRequest->verifikatorTransfer !== null;
    }
}
