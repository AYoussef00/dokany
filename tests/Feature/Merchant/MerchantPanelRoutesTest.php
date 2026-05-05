<?php

declare(strict_types=1);

namespace Tests\Feature\Merchant;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantPanelRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_view_merchant_placeholder_pages(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $this->actingAs($seller);

        $this->get('/merchant/products')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('merchant/Products')
                ->has('products')
                ->has('categoryOptions')
                ->where('filterCategory', null)
                ->has('productCurrencyEn')
                ->has('productCurrencyAr'));

        $this->get('/merchant/categories')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('merchant/Categories')
                ->has('categories', 4));

        $this->get('/merchant/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('merchant/Invoices')
                ->has('invoices'));

        $this->get('/merchant/payments')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('merchant/Payments')
                ->has('payments'));
    }

    public function test_admin_cannot_view_merchant_panel_routes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $this->actingAs($admin);

        foreach (['/merchant/products', '/merchant/categories', '/merchant/invoices', '/merchant/payments'] as $uri) {
            $this->get($uri)->assertForbidden();
        }
    }
}
