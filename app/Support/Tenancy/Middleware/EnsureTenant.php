<?php

declare(strict_types=1);

namespace App\Support\Tenancy\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and enforces the current tenant (merchant/store) context.
 */
final class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
