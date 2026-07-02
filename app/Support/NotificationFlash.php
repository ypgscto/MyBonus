<?php

namespace App\Support;

use App\Services\NotificationResult;
use Illuminate\Http\RedirectResponse;

class NotificationFlash
{
    public static function apply(RedirectResponse $redirect, NotificationResult $notifications): RedirectResponse
    {
        if ($notifications->hasFailures()) {
            $redirect->with('warning', $notifications->warningMessage());
        }

        return $redirect;
    }
}
