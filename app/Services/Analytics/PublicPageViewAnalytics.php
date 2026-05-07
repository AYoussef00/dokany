<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\PageView;
use App\Support\Analytics\PublicPageViewPathRules;

/**
 * Read-only aggregates for public page views (dashboard admin, future reports).
 */
final class PublicPageViewAnalytics
{
    /**
     * @return list<array{path: string, route: string, views: int, avg_seconds: int}>
     */
    public function topPages(int $lastDays, int $limit): array
    {
        return PageView::query()
            ->tap(fn ($q) => PublicPageViewPathRules::scopePublicPaths($q))
            ->selectRaw('path, COALESCE(MAX(component), path) as route, COUNT(*) as views, AVG(duration_seconds) as avg_seconds', [])
            ->where('started_at', '>=', now()->subDays($lastDays))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'path' => (string) $r->path,
                'route' => (string) ($r->route ?? $r->path),
                'views' => (int) $r->views,
                'avg_seconds' => (int) round((float) $r->avg_seconds),
            ])
            ->all();
    }

    /**
     * @return list<array{session: string, user_id: int|null, total_seconds: int, pages: list<array{path: string, route: string, seconds: int}>}>
     */
    public function recentJourneys(int $lastDays, int $maxRows, int $maxSessions): array
    {
        return PageView::query()
            ->tap(fn ($q) => PublicPageViewPathRules::scopePublicPaths($q))
            ->where('started_at', '>=', now()->subDays($lastDays))
            ->orderByDesc('started_at')
            ->limit($maxRows)
            ->get(['session_hash', 'user_id', 'path', 'component', 'duration_seconds', 'started_at'])
            ->groupBy('session_hash')
            ->take($maxSessions)
            ->map(function ($rows, $sessionHash) {
                $sorted = collect($rows)->sortBy('started_at')->values();
                $total = (int) $sorted->sum('duration_seconds');

                return [
                    'session' => (string) $sessionHash,
                    'user_id' => $sorted->first()?->user_id,
                    'total_seconds' => $total,
                    'pages' => $sorted->map(fn ($x) => [
                        'path' => (string) $x->path,
                        'route' => (string) ($x->component ?: $x->path),
                        'seconds' => (int) $x->duration_seconds,
                    ])->all(),
                ];
            })
            ->values()
            ->all();
    }
}
