<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ról: `role:admin` albo `role:admin,magazynier`.
 * "admin" ma zawsze dostęp tam, gdzie wymagana jest rola "magazynier"
 * — pełny dostęp obejmuje uprawnienia niższych ról.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->active) {
            abort(403, __('Account disabled.'));
        }

        if (in_array('magazynier', $roles, true) && $user->isMagazynier()) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, __('You do not have access to this section.'));
        }

        return $next($request);
    }
}
