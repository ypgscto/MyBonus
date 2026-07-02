<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMustChangePassword
{
    /**
     * @var list<string>
     */
    private array $allowedRoutes = [
        'presenter.change-password',
        'presenter.change-password.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->role === UserRole::Presenter
            && $user->must_change_password
            && ! in_array($request->route()?->getName(), $this->allowedRoutes, true)
        ) {
            return redirect()
                ->route('presenter.change-password')
                ->with('warning', 'Anda wajib mengganti password sebelum melanjutkan.');
        }

        return $next($request);
    }
}
