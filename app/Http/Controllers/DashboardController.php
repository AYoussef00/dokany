<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StorefrontOrder;
use App\Models\User;
use App\Services\Admin\AdminPlatformMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminPlatformMetricsService $adminMetrics): Response
    {
        $user = $request->user();

        $dashboardStats = null;

        if ($user !== null && $user->isAdmin()) {
            $dashboardStats = $adminMetrics->buildDashboardStats();
        }

        $sellerDashboardStats = null;

        if ($user !== null && $user->isSeller()) {
            $paymentsSum = (float) $user->merchantPayments()->sum('amount');
            $paymentsCurrencyEn = $user->merchantPayments()->value('currency_label_en');
            $storefrontPrefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');
            $storefrontUrl = null;
            if (is_string($user->store_slug) && $user->store_slug !== '') {
                $storefrontUrl = '/'.$storefrontPrefix.'/'.$user->store_slug;
            }

            $orderBase = StorefrontOrder::query()->where('user_id', $user->id);

            $ordersToday = (clone $orderBase)->whereDate('created_at', now()->toDateString())->count();
            $ordersTotal = (clone $orderBase)->count();

            $statusMeta = [
                StorefrontOrder::STATUS_PENDING_PAYMENT => [
                    'label' => 'قيد انتظار الدفع',
                    'color' => '#60a5fa',
                ],
                StorefrontOrder::STATUS_PAYMENT_SUBMITTED => [
                    'label' => 'تم إرسال الإيصال',
                    'color' => '#a78bfa',
                ],
                StorefrontOrder::STATUS_ACCEPTED => [
                    'label' => 'مؤكدة',
                    'color' => '#34d399',
                ],
                StorefrontOrder::STATUS_REJECTED => [
                    'label' => 'مرفوضة',
                    'color' => '#f87171',
                ],
            ];

            $orderStatusBreakdown = [];
            foreach ($statusMeta as $status => $meta) {
                $orderStatusBreakdown[] = [
                    'status' => $status,
                    'label' => $meta['label'],
                    'color' => $meta['color'],
                    'count' => (clone $orderBase)->where('status', $status)->count(),
                ];
            }

            $arabicMonths = [
                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
            ];

            $ordersMonthlyTrend = [];
            $monthCursor = now()->startOfMonth()->subMonths(5);
            for ($i = 0; $i < 6; $i++) {
                /** @var Carbon $m */
                $m = $monthCursor->copy()->addMonths($i);
                $ordersMonthlyTrend[] = [
                    'label' => $arabicMonths[$m->month] ?? $m->format('m'),
                    'value' => (clone $orderBase)
                        ->whereYear('created_at', $m->year)
                        ->whereMonth('created_at', $m->month)
                        ->count(),
                ];
            }

            $firstOrderAt = (clone $orderBase)->orderBy('created_at')->value('created_at');
            $lastOrderAt = (clone $orderBase)->orderByDesc('created_at')->value('created_at');

            $ordersFirstAtIso = null;
            if ($firstOrderAt !== null) {
                $ordersFirstAtIso = Carbon::parse($firstOrderAt)->toIso8601String();
            }
            $ordersLastAtIso = null;
            if ($lastOrderAt !== null) {
                $ordersLastAtIso = Carbon::parse($lastOrderAt)->toIso8601String();
            }

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
                'orders_today_count' => $ordersToday,
                'orders_total_count' => $ordersTotal,
                'order_status_breakdown' => $orderStatusBreakdown,
                'orders_monthly_trend' => $ordersMonthlyTrend,
                'orders_first_at' => $ordersFirstAtIso,
                'orders_last_at' => $ordersLastAtIso,
                'storefront_url' => $storefrontUrl,
            ];
        }

        return Inertia::render('Dashboard', [
            'dashboardStats' => $dashboardStats,
            'sellerDashboardStats' => $sellerDashboardStats,
        ]);
    }
}
