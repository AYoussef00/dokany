<?php

declare(strict_types=1);

namespace Tests\Feature\Merchant;

use App\Models\MerchantInvoice;
use App\Models\MerchantPayment;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_seller_cannot_view_merchant_orders(): void
    {
        $buyer = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($buyer)->get(route('merchant.orders'))->assertForbidden();
    }

    public function test_seller_sees_only_their_storefront_orders(): void
    {
        $sellerA = $this->activeSeller();
        $sellerB = $this->activeSeller();

        $orderA = $this->makeOrder($sellerA, buyerName: 'عميل أ');
        $orderB = $this->makeOrder($sellerB, buyerName: 'عميل ب');

        $this->actingAs($sellerA)
            ->get(route('merchant.orders'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('merchant/Orders')
                ->has('orders', 1)
                ->where('orders.0.uuid', $orderA->uuid)
                ->where('orders.0.buyer_name', 'عميل أ'));

        $this->assertModelExists($orderB);
    }

    public function test_seller_can_accept_payment_submitted_order(): void
    {
        $seller = $this->activeSeller();
        $order = $this->makeOrder($seller, status: StorefrontOrder::STATUS_PAYMENT_SUBMITTED);

        $this->actingAs($seller)
            ->post(route('merchant.orders.accept', $order))
            ->assertRedirect(route('merchant.orders'));

        $order->refresh();
        $this->assertSame(StorefrontOrder::STATUS_ACCEPTED, $order->status);

        $this->assertDatabaseHas('merchant_invoices', [
            'storefront_order_id' => $order->id,
            'user_id' => $seller->id,
        ]);
        $this->assertDatabaseHas('merchant_payments', [
            'storefront_order_id' => $order->id,
            'user_id' => $seller->id,
        ]);
        $this->assertSame(1, MerchantInvoice::query()->where('storefront_order_id', $order->id)->count());
        $this->assertSame(1, MerchantPayment::query()->where('storefront_order_id', $order->id)->count());
    }

    public function test_seller_can_reject_payment_submitted_order(): void
    {
        $seller = $this->activeSeller();
        $order = $this->makeOrder($seller, status: StorefrontOrder::STATUS_PAYMENT_SUBMITTED);

        $this->actingAs($seller)
            ->post(route('merchant.orders.reject', $order))
            ->assertRedirect(route('merchant.orders'));

        $order->refresh();
        $this->assertSame(StorefrontOrder::STATUS_REJECTED, $order->status);
    }

    public function test_seller_cannot_accept_another_sellers_order(): void
    {
        $sellerA = $this->activeSeller();
        $sellerB = $this->activeSeller();
        $order = $this->makeOrder($sellerB, status: StorefrontOrder::STATUS_PAYMENT_SUBMITTED);

        $this->actingAs($sellerA)
            ->post(route('merchant.orders.accept', $order))
            ->assertForbidden();
    }

    public function test_seller_cannot_accept_pending_payment_order(): void
    {
        $seller = $this->activeSeller();
        $order = $this->makeOrder($seller, status: StorefrontOrder::STATUS_PENDING_PAYMENT);

        $this->actingAs($seller)
            ->post(route('merchant.orders.accept', $order))
            ->assertRedirect(route('merchant.orders'));

        $this->assertTrue(session()->has('error'));

        $order->refresh();
        $this->assertSame(StorefrontOrder::STATUS_PENDING_PAYMENT, $order->status);
    }

    public function test_seller_can_delete_pending_payment_order(): void
    {
        $seller = $this->activeSeller();
        $order = $this->makeOrder($seller, status: StorefrontOrder::STATUS_PENDING_PAYMENT);

        $this->actingAs($seller)
            ->delete(route('merchant.orders.destroy', $order))
            ->assertRedirect(route('merchant.orders'));

        $this->assertModelMissing($order);
    }

    public function test_seller_can_delete_rejected_order(): void
    {
        $seller = $this->activeSeller();
        $order = $this->makeOrder($seller, status: StorefrontOrder::STATUS_REJECTED);

        $this->actingAs($seller)
            ->delete(route('merchant.orders.destroy', $order))
            ->assertRedirect(route('merchant.orders'));

        $this->assertModelMissing($order);
    }

    public function test_seller_can_delete_payment_submitted_order_and_removes_receipt_from_disk(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('storefront-order-receipts/test.jpg', 'x');

        $seller = $this->activeSeller();
        $order = $this->makeOrder($seller, status: StorefrontOrder::STATUS_PAYMENT_SUBMITTED);
        $order->payment_receipt_path = 'storefront-order-receipts/test.jpg';
        $order->save();

        $this->actingAs($seller)
            ->delete(route('merchant.orders.destroy', $order))
            ->assertRedirect(route('merchant.orders'));

        $this->assertModelMissing($order);
        Storage::disk('public')->assertMissing('storefront-order-receipts/test.jpg');
    }

    public function test_seller_cannot_delete_accepted_order(): void
    {
        $seller = $this->activeSeller();
        $order = $this->makeOrder($seller, status: StorefrontOrder::STATUS_PAYMENT_SUBMITTED);

        $this->actingAs($seller)
            ->post(route('merchant.orders.accept', $order))
            ->assertRedirect(route('merchant.orders'));

        $order->refresh();
        $this->assertSame(StorefrontOrder::STATUS_ACCEPTED, $order->status);

        $this->actingAs($seller)
            ->delete(route('merchant.orders.destroy', $order))
            ->assertRedirect(route('merchant.orders'));

        $this->assertTrue(session()->has('error'));
        $this->assertModelExists($order);
    }

    public function test_seller_cannot_delete_another_sellers_order(): void
    {
        $sellerA = $this->activeSeller();
        $sellerB = $this->activeSeller();
        $order = $this->makeOrder($sellerB, status: StorefrontOrder::STATUS_PENDING_PAYMENT);

        $this->actingAs($sellerA)
            ->delete(route('merchant.orders.destroy', $order))
            ->assertForbidden();

        $this->assertModelExists($order);
    }

    private function activeSeller(): User
    {
        return User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);
    }

    private function makeOrder(User $seller, string $buyerName = 'مشتري', string $status = StorefrontOrder::STATUS_PAYMENT_SUBMITTED): StorefrontOrder
    {
        $order = new StorefrontOrder([
            'uuid' => (string) Str::uuid(),
            'buyer_name' => $buyerName,
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
