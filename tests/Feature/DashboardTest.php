<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_seller_dashboard_includes_product_order_and_invoice_counts(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        Product::factory()->count(2)->create(['user_id' => $seller->id]);

        $this->makeStorefrontOrder($seller, StorefrontOrder::STATUS_PAYMENT_SUBMITTED);
        $this->makeStorefrontOrder($seller, StorefrontOrder::STATUS_PENDING_PAYMENT);

        $toAccept = $this->makeStorefrontOrder($seller, StorefrontOrder::STATUS_PAYMENT_SUBMITTED);

        $this->actingAs($seller)
            ->post(route('merchant.orders.accept', $toAccept))
            ->assertRedirect(route('merchant.orders'));

        $this->actingAs($seller)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('sellerDashboardStats.total_revenue', 10)
                ->where('sellerDashboardStats.currency_en', 'EGP')
                ->where('sellerDashboardStats.products_count', 2)
                ->where('sellerDashboardStats.confirmed_orders_count', 1)
                ->where('sellerDashboardStats.new_orders_count', 2)
                ->where('sellerDashboardStats.invoices_count', 1)
                ->where('sellerDashboardStats.storefront_visits_count', 0));
    }

    private function makeStorefrontOrder(User $seller, string $status): StorefrontOrder
    {
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
            'status' => $status,
            'payment_receipt_path' => $status === StorefrontOrder::STATUS_PAYMENT_SUBMITTED ? 'r/a.jpg' : null,
        ]);
        $order->seller()->associate($seller);
        $order->save();

        return $order;
    }
}
