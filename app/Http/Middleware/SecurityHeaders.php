<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add baseline security headers.
     *
     * Note: CSP is intentionally "baseline" because the app currently uses inline scripts in the main layout.
     * You can tighten CSP later by moving inline scripts to files and using nonces.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Baseline CSP (compatible with current app.blade.php inline script/styles).
        // Tighten later (remove unsafe-inline, add nonces) when possible.
        $headers->set(
            'Content-Security-Policy',
            "default-src 'self'; ".
            "img-src 'self' data: https:; ".
            "font-src 'self' data: https:; ".
            "style-src 'self' 'unsafe-inline' https:; ".
            "script-src 'self' 'unsafe-inline' https:; ".
            "connect-src 'self' https:; ".
            "frame-ancestors 'self'; ".
            "base-uri 'self'; ".
            "form-action 'self'"
        );

        return $response;
    }
}

