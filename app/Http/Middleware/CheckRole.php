<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 *
 * Mirrors requireAuth() from src/lib/auth.ts:
 *   requireAuth(['CS', 'SUPER_ADMIN']) — allows listed roles
 *   requireAuth(['SUPER_ADMIN'])       — allows only super admin
 *
 * Usage in routes:
 *   Route::middleware(['auth', 'role:CS,SUPER_ADMIN,DEVELOPER'])
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthenticated.');
        }

        $userRoles = $user->roles->pluck('code')->map(fn($c) => $c->value)->toArray();

        // Allow if the user has ANY of the required roles
        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    }
}
