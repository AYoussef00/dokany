<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $dashboardStats = null;

        if ($user !== null && $user->isAdmin()) {
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

            $dashboardStats = [
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
                'total_storefront_orders' => StorefrontOrder::query()->count(),
            ];
        }

        $sellerDashboardStats = null;

        if ($user !== null && $user->isSeller()) {
            $paymentsSum = (float) $user->merchantPayments()->sum('amount');
            $paymentsCurrencyEn = $user->merchantPayments()->value('currency_label_en');

            $sellerDashboardStats = [
                'total_revenue' => round($paymentsSum, 2),
                'currency_en' => $paymentsCurrencyEn !== null && $paymentsCurrencyEn !== ''
                    ? (string) $paymentsCurrencyEn
                    : (string) config('dokany.subscription.currency_label_en', 'EGP'),
                'products_count' => $user->products()->count(),
                'confirmed_orders_count' => $user->storefrontOrders()
                    ->where('status', StorefrontOrder::STATUS_ACCEPTED)
                    ->count(),
                'new_orders_count' => $user->storefrontOrders()
                    ->whereIn('status', [
                        StorefrontOrder::STATUS_PENDING_PAYMENT,
                        StorefrontOrder::STATUS_PAYMENT_SUBMITTED,
                    ])
                    ->count(),
                'invoices_count' => $user->merchantInvoices()->count(),
                'storefront_visits_count' => (int) $user->storefront_visit_count,
            ];
        }

        return Inertia::render('Dashboard', [
            'dashboardStats' => $dashboardStats,
            'sellerDashboardStats' => $sellerDashboardStats,
        ]);
    }
}
