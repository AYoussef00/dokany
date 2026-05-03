<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_includes_revenue_merchants_and_requests_counts(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);

        User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
            'subscription_paid_amount' => 250.5,
        ]);

        User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
            'subscription_paid_amount' => null,
            'subscription_payment_reported_at' => now(),
        ]);

        User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW,
            'email_verified_at' => now(),
        ]);

        $sellerWithCatalog = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);
        Product::factory()->count(2)->create(['user_id' => $sellerWithCatalog->id]);

        $sellerWithOrder = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $order = new StorefrontOrder([
            'uuid' => (string) Str::uuid(),
            'buyer_name' => 'مشتري',
            'buyer_phone' => '01000000000',
            'buyer_address' => 'عنوان',
            'buyer_maps_url' => null,
            'lines' => [
                ['product_id' => 1, 'name' => 'صنف', 'quantity' => 1, 'unit_price' => '10.00', 'line_total' => '10.00'],
            ],
            'subtotal' => '10.00',
            'currency_label_ar' => 'ج.م',
            'currency_label_en' => 'EGP',
            'status' => StorefrontOrder::STATUS_PENDING_PAYMENT,
            'payment_receipt_path' => null,
        ]);
        $order->seller()->associate($sellerWithOrder);
        $order->save();

        $platformAmount = (int) config('dokany.subscription.amount', 500);
        $expectedRevenue = round(250.5 + $platformAmount, 2);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('dashboardStats')
                ->where('dashboardStats.active_merchants_count', 4)
                ->where('dashboardStats.pending_requests_count', 1)
                ->where('dashboardStats.total_products_all_sellers', 2)
                ->where('dashboardStats.total_storefront_orders', 1)
                ->where('dashboardStats.total_revenue', $expectedRevenue));
    }

    public function test_non_admin_dashboard_has_null_dashboard_stats(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('dashboardStats', null));
    }
}
