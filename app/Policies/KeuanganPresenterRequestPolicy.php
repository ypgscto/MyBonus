<?php

namespace App\Policies;

use App\Enums\PresenterRequestStatus;
use App\Enums\UserRole;
use App\Models\PresenterRequest;
use App\Models\User;

class KeuanganPresenterRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Keuangan;
    }

    public function view(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Keuangan;
    }

    public function confirmReceived(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Keuangan
            && $presenterRequest->status === PresenterRequestStatus::TransferredToFinance;
    }

    public function transferToPresenter(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Keuangan
            && $presenterRequest->status === PresenterRequestStatus::ReceivedByFinance;
    }

    public function close(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Keuangan
            && $presenterRequest->status === PresenterRequestStatus::TransferredToPresenter;
    }

    public function downloadVerifikatorTransferProof(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Keuangan
            && $presenterRequest->verifikatorTransfer !== null;
    }

    public function downloadPresenterTransferProof(User $user, PresenterRequest $presenterRequest): bool
    {
        return $user->role === UserRole::Keuangan
            && $presenterRequest->presenterTransfer !== null;
    }
}
