<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $base = rtrim($request->getSchemeAndHttpHost(), '/');
        $storefrontPrefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');
        $now = now()->toAtomString();

        $urls = [
            ['loc' => $base.'/', 'lastmod' => $now, 'changefreq' => 'daily', 'priority' => '1.0'],
        ];

        User::query()
            ->where('role', User::ROLE_SELLER)
            ->where('merchant_subscription_status', User::MERCHANT_SUBSCRIPTION_ACTIVE)
            ->whereNotNull('store_slug')
            ->orderBy('updated_at', 'desc')
            ->limit(5000)
            ->get(['store_slug', 'updated_at'])
            ->each(function (User $u) use (&$urls, $base, $storefrontPrefix): void {
                $slug = (string) $u->store_slug;
                if ($slug === '') {
                    return;
                }
                $urls[] = [
                    'loc' => $base.'/'.$storefrontPrefix.'/'.$slug,
                    'lastmod' => optional($u->updated_at)->toAtomString() ?? now()->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'."\n";
            $xml .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            $xml .= '    <changefreq>'.$u['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
        ]);
    }
}
