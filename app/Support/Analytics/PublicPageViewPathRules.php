<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for which URL paths are excluded from public analytics.
 */
final class PublicPageViewPathRules
{
    /**
     * @return list<string>
     */
    public static function excludedPrefixes(): array
    {
        /** @var list<string> $prefixes */
        $prefixes = config('dokany.analytics.public_page_view_exclude_prefixes', []);

        return array_values(array_filter(array_map(
            static fn (string $p): string => '/'.ltrim($p, '/'),
            $prefixes,
        )));
    }

    public static function isExcludedFromTracking(string $path): bool
    {
        $normalized = '/'.ltrim($path, '/');

        foreach (self::excludedPrefixes() as $prefix) {
            $base = rtrim($prefix, '/') ?: '/';
            if ($normalized === $base || str_starts_with($normalized, $base.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Builder<\App\Models\PageView>  $query
     * @return Builder<\App\Models\PageView>
     */
    public static function scopePublicPaths(Builder $query): Builder
    {
        foreach (self::excludedPrefixes() as $prefix) {
            $pattern = rtrim($prefix, '/').'%';
            $query->where('path', 'not like', $pattern);
        }

        return $query;
    }
}
