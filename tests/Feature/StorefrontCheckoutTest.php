<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MerchantInvoice;
use App\Models\MerchantPayment;
use App\Models\Product;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_loads_for_active_seller(): void
    {
        $seller = $this->activeSeller('checkout-demo');

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->get('/'.$prefix.'/checkout-demo/checkout')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Checkout')
                ->where('seller.name', $seller->name)
                ->where('seller.instapay_wallet', $seller->instapay_wallet)
                ->where('checkoutPostPath', '/'.$prefix.'/checkout-demo/checkout')
                ->has('paymentInstructions'));
    }

    public function test_checkout_store_creates_order_and_redirects_to_payment(): void
    {
        $seller = $this->activeSeller('pay-demo');
        $seller->instapay_wallet = 'demo-wallet-123';
        $seller->save();

        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'name' => 'منتج اختبار',
            'price' => 100.5,
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $response = $this->post('/'.$prefix.'/pay-demo/checkout', [
            'buyer_name' => 'أحمد',
            'buyer_phone' => '01000000000',
            'buyer_address' => 'القاهرة',
            'buyer_maps_url' => 'https://maps.google.com/?q=1,2',
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $order = StorefrontOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame($seller->id, $order->user_id);
        $this->assertSame('أحمد', $order->buyer_name);
        $this->assertSame('201.00', (string) $order->subtotal);
        $this->assertCount(1, $order->lines);
        $this->assertSame('منتج اختبار', $order->lines[0]['name']);

        $response->assertRedirect('/'.$prefix.'/pay-demo/order/'.$order->uuid.'/pay');

        $this->assertSame(0, MerchantInvoice::query()->where('storefront_order_id', $order->id)->count());
        $this->assertSame(0, MerchantPayment::query()->where('storefront_order_id', $order->id)->count());
    }

    public function test_payment_page_accepts_receipt_and_marks_order_submitted(): void
    {
        Storage::fake('public');

        $seller = $this->activeSeller('receipt-demo');
        $seller->instapay_wallet = 'w';
        $seller->save();

        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'price' => 10,
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $response = $this->post('/'.$prefix.'/receipt-demo/checkout', [
            'buyer_name' => 'x',
            'buyer_phone' => '1',
            'buyer_address' => 'y',
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);
        $response->assertRedirect();

        $order = StorefrontOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(0, MerchantInvoice::query()->where('storefront_order_id', $order->id)->count());
        $this->assertSame(0, MerchantPayment::query()->where('storefront_order_id', $order->id)->count());

        $file = UploadedFile::fake()->image('receipt.jpg');

        $this->post('/'.$prefix.'/receipt-demo/order/'.$order->uuid.'/pay', [
            'payment_receipt' => $file,
        ])
            ->assertRedirect('/'.$prefix.'/receipt-demo')
            ->assertSessionHas('payment_receipt_received', true);

        $order->refresh();
        $this->assertSame(StorefrontOrder::STATUS_PAYMENT_SUBMITTED, $order->status);
        $this->assertNotNull($order->payment_receipt_path);
        Storage::disk('public')->assertExists($order->payment_receipt_path);
        $this->assertSame(0, MerchantInvoice::query()->where('storefront_order_id', $order->id)->count());
        $this->assertSame(0, MerchantPayment::query()->where('storefront_order_id', $order->id)->count());
    }

    public function test_payment_show_redirects_to_storefront_when_receipt_already_submitted(): void
    {
        $seller = $this->activeSeller('already-paid-demo');

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $order = new StorefrontOrder([
            'uuid' => 'test-order-uuid-already-paid',
            'buyer_name' => 'عميل',
            'buyer_phone' => '01000000000',
            'buyer_address' => 'عنوان',
            'buyer_maps_url' => null,
            'lines' => [
                ['product_id' => 1, 'name' => 'صنف', 'quantity' => 1, 'unit_price' => '10.00', 'line_total' => '10.00'],
            ],
            'subtotal' => '10.00',
            'currency_label_ar' => 'ج.م',
            'currency_label_en' => 'EGP',
            'status' => StorefrontOrder::STATUS_PAYMENT_SUBMITTED,
            'payment_receipt_path' => 'receipts/x.jpg',
        ]);
        $order->seller()->associate($seller);
        $order->save();

        $this->get('/'.$prefix.'/already-paid-demo/order/'.$order->uuid.'/pay')
            ->assertRedirect('/'.$prefix.'/already-paid-demo')
            ->assertSessionHas('payment_receipt_received', true);
    }

    public function test_checkout_rejects_foreign_product_id(): void
    {
        $seller = $this->activeSeller('safe-demo');
        $other = $this->activeSeller('other-shop');

        $foreign = Product::factory()->create([
            'user_id' => $other->id,
            'price' => 99,
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->post('/'.$prefix.'/safe-demo/checkout', [
            'buyer_name' => 'a',
            'buyer_phone' => 'b',
            'buyer_address' => 'c',
            'lines' => [
                ['product_id' => $foreign->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors(['lines']);

        $this->assertSame(0, StorefrontOrder::query()->count());
    }

    private function activeSeller(string $slug): User
    {
        return User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => $slug,
            'name' => 'متجر '.$slug,
        ]);
    }
}
