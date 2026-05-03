<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_storefront_shows_active_seller_and_products(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'demo-boutique',
            'name' => 'متجر العرض',
            'whatsapp_phone' => '201012345678',
        ]);

        $product = Product::factory()->create([
            'user_id' => $seller->id,
            'name' => 'صنف تجريبي',
            'description' => 'وصف',
            'price' => 55.5,
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->get('/'.$prefix.'/demo-boutique')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Storefront')
                ->where('seller.name', 'متجر العرض')
                ->where('seller.hero_primary', null)
                ->where('seller.hero_secondary', null)
                ->where('seller.hero_banner_urls', [])
                ->has('products', 1)
                ->where('products.0.name', 'صنف تجريبي')
                ->where('checkoutPath', '/'.$prefix.'/demo-boutique/checkout'));

        $this->assertModelExists($product);
    }

    public function test_storefront_visit_increments_once_per_session(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'visit-counter-shop',
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->assertSame(0, $seller->fresh()->storefront_visit_count);

        $this->get('/'.$prefix.'/visit-counter-shop')->assertOk();
        $this->assertSame(1, $seller->fresh()->storefront_visit_count);

        $this->get('/'.$prefix.'/visit-counter-shop')->assertOk();
        $this->assertSame(1, $seller->fresh()->storefront_visit_count);
    }

    public function test_storefront_visit_increments_after_new_session(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'visit-two-sessions',
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->get('/'.$prefix.'/visit-two-sessions')->assertOk();
        $this->assertSame(1, $seller->fresh()->storefront_visit_count);

        $this->flushSession();

        $this->get('/'.$prefix.'/visit-two-sessions')->assertOk();
        $this->assertSame(2, $seller->fresh()->storefront_visit_count);
    }

    public function test_inactive_seller_storefront_returns_404(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_INACTIVE,
            'store_slug' => 'closed-shop',
        ]);

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');

        $this->get('/'.$prefix.'/closed-shop')->assertNotFound();
        $this->assertModelExists($seller);
    }
}
