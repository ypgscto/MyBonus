<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($this->isApiRequest($request)) {
                return ApiResponse::error('Unauthenticated.', 401, code: 'UNAUTHENTICATED');
            }

            return redirect()->route('login');
        }

        $allowedRoles = array_map(
            fn (string $role) => UserRole::from($role),
            $roles
        );

        if (! in_array($user->role, $allowedRoles, true)) {
            if ($this->isApiRequest($request)) {
                return ApiResponse::error('Akses ditolak untuk role Anda.', 403, code: 'FORBIDDEN');
            }

            abort(403);
        }

        return $next($request);
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}
