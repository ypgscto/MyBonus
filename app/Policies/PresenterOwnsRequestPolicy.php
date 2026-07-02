<?php

namespace App\Policies;

use App\Models\Presenter;
use App\Models\PresenterRequest;
use App\Models\User;

class PresenterOwnsRequestPolicy
{
    public function view(User $user, PresenterRequest $request): bool
    {
        return $this->ownsRequest($user, $request);
    }

    public function viewTransferProof(User $user, PresenterRequest $request): bool
    {
        if (! $this->ownsRequest($user, $request)) {
            return false;
        }

        return in_array($request->status->value, [
            'transferred_to_presenter',
            'closed',
        ], true) && $request->presenterTransfer !== null;
    }

    private function ownsRequest(User $user, PresenterRequest $request): bool
    {
        $presenter = Presenter::query()->where('user_id', $user->id)->first();

        return $presenter !== null && (int) $request->presenter_id === (int) $presenter->id;
    }
}
