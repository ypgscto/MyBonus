<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Presenter;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesAuthenticatedPresenter
{
    protected function authenticatedPresenter(): Presenter
    {
        $presenter = Presenter::forAuthenticatedUser();

        if (! $presenter) {
            throw new HttpException(403, 'Akun presenter tidak terhubung dengan data master presenter.');
        }

        return $presenter;
    }
}
