<?php

namespace App\Http\Middleware;

use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $sellerAccess = null;
        if (
            $user instanceof User
            && $user->role === User::ROLE_SELLER
            && $user->merchant_subscription_status === User::MERCHANT_SUBSCRIPTION_ACTIVE
        ) {
            $sellerAccess = [
                'accessEndsAt' => $user->merchantAccessEndsAt()?->toIso8601String(),
                'accessDays' => max(1, (int) config('dokany.subscription.merchant_access_days', 30)),
                'accessMonths' => $user->merchant_access_months !== null ? (int) $user->merchant_access_months : null,
                'isExpired' => $user->merchantAccessExpired(),
            ];
        }

        $sellerNavBadges = null;
        if ($user instanceof User && $user->isSeller()) {
            $sellerNavBadges = [
                'pending_orders' => $user->storefrontOrders()->whereIn('status', [
                    StorefrontOrder::STATUS_PENDING_PAYMENT,
                    StorefrontOrder::STATUS_PAYMENT_SUBMITTED,
                ])->count(),
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'sellerAccess' => $sellerAccess,
            'sellerNavBadges' => $sellerNavBadges,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'payment_receipt_received' => $request->session()->get('payment_receipt_received'),
            ],
        ];
    }
}
