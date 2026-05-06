<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class TrackSiteVisits
{
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request)) {
            return $response;
        }

        try {
            $now = now();
            $sessionId = (string) $request->session()->getId();
            if ($sessionId === '') {
                return $response;
            }

            // Count unique visitors per day per session.
            $uniqKey = 'site_visit:'.now()->format('Y-m-d').':'.hash('sha256', $sessionId);
            if (Cache::has($uniqKey)) {
                return $response;
            }

            Cache::put($uniqKey, 1, now()->addDay());

            $ip = $request->ip();
            $geo = $this->lookupCountry($ip);

            SiteVisit::query()->create([
                'visited_at' => $now,
                'user_id' => $request->user()?->id,
                'session_hash' => hash('sha256', $sessionId),
                'ip' => $ip,
                'country_code' => $geo['code'] ?? null,
                'country_name' => $geo['name'] ?? null,
                'path' => '/'.ltrim($request->path(), '/'),
                'user_agent' => substr((string) $request->userAgent(), 0, 512) ?: null,
            ]);
        } catch (\Throwable) {
            // Do not block requests for analytics issues.
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Skip assets and hot reload endpoints.
        if (
            $request->is('build/*')
            || $request->is('storage/*')
            || $request->is('favicon.ico')
            || $request->is('up')
        ) {
            return false;
        }

        // Track only browser navigations (HTML or Inertia visits).
        if ($request->header('X-Inertia') === 'true') {
            return true;
        }

        return $request->acceptsHtml();
    }

    /**
     * Returns ['code' => 'EG', 'name' => 'Egypt'] or nulls.
     */
    private function lookupCountry(?string $ip): array
    {
        if (! is_string($ip) || $ip === '') {
            return [];
        }

        $cacheKey = 'geoip:country:'.$ip;
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        // Uses ipapi.co (no key). If it fails, we return empty.
        $timeout = (int) (config('services.geoip.timeout_seconds') ?? 2);
        $url = (string) (config('services.geoip.endpoint') ?? 'https://ipapi.co/{ip}/json/');
        $url = str_replace('{ip}', rawurlencode($ip), $url);

        try {
            $res = Http::timeout(max(1, $timeout))
                ->acceptJson()
                ->get($url);

            if (! $res->ok()) {
                Cache::put($cacheKey, [], now()->addDays(7));
                return [];
            }

            $data = $res->json();
            $out = [
                'code' => is_array($data) ? ($data['country_code'] ?? null) : null,
                'name' => is_array($data) ? ($data['country_name'] ?? null) : null,
            ];

            Cache::put($cacheKey, $out, now()->addDays(7));
            return $out;
        } catch (\Throwable) {
            Cache::put($cacheKey, [], now()->addDays(1));
            return [];
        }
    }
}

