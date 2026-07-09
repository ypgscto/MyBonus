<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMustChangePassword
{
    /**
     * @var list<string>
     */
    private array $allowedRoutePatterns = [
        'presenter.change-password',
        'presenter.change-password.update',
        'logout',
        'api.*.auth.change-password',
        'api.*.auth.logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->role === UserRole::Presenter
            && $user->must_change_password
            && ! $this->isAllowedRoute($request)
        ) {
            if ($this->isApiRequest($request)) {
                return ApiResponse::error(
                    'Anda wajib mengganti password sebelum melanjutkan.',
                    403,
                    code: 'MUST_CHANGE_PASSWORD'
                );
            }

            return redirect()
                ->route('presenter.change-password')
                ->with('warning', 'Anda wajib mengganti password sebelum melanjutkan.');
        }

        return $next($request);
    }

    private function isAllowedRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return false;
        }

        foreach ($this->allowedRoutePatterns as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
