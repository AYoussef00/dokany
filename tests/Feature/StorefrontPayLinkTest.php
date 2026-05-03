<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PaymentLink;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontPayLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_link_show_and_store_creates_order_and_redirects_to_payment(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'plink-demo',
        ]);

        $link = PaymentLink::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $seller->id,
            'amount' => '250.00',
            'currency_label_ar' => 'ج.م',
            'currency_label_en' => 'EGP',
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->get('/'.$prefix.'/plink-demo/pay-link/'.$link->uuid)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/StorefrontPayLink')
                ->where('link.uuid', $link->uuid));

        $response = $this->post('/'.$prefix.'/plink-demo/pay-link/'.$link->uuid, [
            'buyer_name' => 'عميل',
            'buyer_phone' => '01000000000',
            'buyer_address' => 'القاهرة',
        ]);

        $order = StorefrontOrder::query()->first();
        $this->assertNotNull($order);
        $this->assertSame($seller->id, $order->user_id);
        $this->assertSame($link->id, $order->payment_link_id);
        $this->assertSame('250.00', (string) $order->subtotal);
        $this->assertSame(StorefrontOrder::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertCount(1, $order->lines);
        $this->assertSame('دفع عبر رابط الدفع', $order->lines[0]['name']);

        $response->assertRedirect('/'.$prefix.'/plink-demo/order/'.$order->uuid.'/pay');
    }

    public function test_pay_link_returns_404_for_wrong_seller_slug(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'store-a',
        ]);

        $link = PaymentLink::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $seller->id,
            'amount' => '10.00',
            'currency_label_ar' => 'ج.م',
            'currency_label_en' => 'EGP',
        ]);

        $other = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'store-b',
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->get('/'.$prefix.'/store-b/pay-link/'.$link->uuid)->assertNotFound();
        $this->assertModelExists($other);
    }
}
