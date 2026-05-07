<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\SiteVisit;
use App\Models\StorefrontOrder;
use App\Models\User;
use App\Services\Analytics\PublicPageViewAnalytics;

/**
 * Composes admin dashboard KPIs. Keeps DashboardController thin and testable.
 */
final class AdminPlatformMetricsService
{
    public function __construct(
        private readonly PublicPageViewAnalytics $publicPageViews,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildDashboardStats(): array
    {
        $platformAmount = (float) config('dokany.subscription.amount', 0);

        $activeSellers = User::query()
            ->where('role', User::ROLE_SELLER)
            ->where('merchant_subscription_status', User::MERCHANT_SUBSCRIPTION_ACTIVE)
            ->get(['subscription_paid_amount', 'subscription_payment_reported_at']);

        $totalRevenue = 0.0;
        foreach ($activeSellers as $seller) {
            if ($seller->subscription_paid_amount !== null) {
                $totalRevenue += (float) $seller->subscription_paid_amount;
            } elseif ($seller->subscription_payment_reported_at !== null) {
                $totalRevenue += $platformAmount;
            }
        }

        return [
            'total_revenue' => round($totalRevenue, 2),
            'currency_en' => (string) config('dokany.subscription.currency_label_en', 'EGP'),
            'active_merchants_count' => $activeSellers->count(),
            'pending_requests_count' => User::query()
                ->where('role', User::ROLE_SELLER)
                ->where('merchant_subscription_status', User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW)
                ->count(),
            'total_products_all_sellers' => Product::query()
                ->whereHas('user', fn ($q) => $q->where('role', User::ROLE_SELLER))
                ->count(),
            'total_storefront_orders' => StorefrontOrder::query()->count('*'),
            'visitors_today' => SiteVisit::query()
                ->where('visited_at', '>=', now()->startOfDay())
                ->distinct()
                ->count('session_hash'),
            'visitors_total' => SiteVisit::query()
                ->distinct()
                ->count('session_hash'),
            'top_countries_30d' => SiteVisit::query()
                ->selectRaw('COALESCE(country_name, \'Unknown\') as country, COUNT(DISTINCT session_hash) as visitors', [])
                ->where('visited_at', '>=', now()->subDays(30))
                ->groupBy('country')
                ->orderByDesc('visitors')
                ->limit(5)
                ->get()
                ->map(fn ($r) => ['country' => $r->country, 'visitors' => (int) $r->visitors])
                ->all(),
            'top_pages_30d' => $this->publicPageViews->topPages(30, 7),
            'recent_journeys' => $this->publicPageViews->recentJourneys(7, 200, 15),
        ];
    }
}
