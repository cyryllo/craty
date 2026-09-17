<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `module:sales` itd. — 404, nie 403 (spójnie z MarketplaceController),
 * żeby wyłączony moduł wyglądał jak "tego tu nie ma", nie "brak uprawnień".
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(Modules::isEnabled($module), 404);

        return $next($request);
    }
}
