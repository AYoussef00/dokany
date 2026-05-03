<?php

declare(strict_types=1);

namespace Tests\Feature\Merchant;

use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantPaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_view_payment_links_page(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'paylink-store',
        ]);

        $this->actingAs($seller)
            ->get(route('merchant.payment-links'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('merchant/PaymentLinks')
                ->has('links')
                ->where('canPublishLinks', true));
    }

    public function test_seller_without_slug_cannot_publish_links(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => null,
        ]);

        $this->actingAs($seller)
            ->get(route('merchant.payment-links'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canPublishLinks', false));
    }

    public function test_seller_can_create_payment_link(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'abc-shop',
        ]);

        $this->actingAs($seller)
            ->post(route('merchant.payment-links.store'), ['amount' => '99.50'])
            ->assertRedirect(route('merchant.payment-links'));

        $this->assertDatabaseHas('payment_links', [
            'user_id' => $seller->id,
            'amount' => '99.50',
        ]);
    }

    public function test_seller_without_slug_cannot_store_payment_link(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => null,
        ]);

        $this->actingAs($seller)
            ->post(route('merchant.payment-links.store'), ['amount' => '10'])
            ->assertRedirect(route('merchant.payment-links'));

        $this->assertTrue(session()->has('error'));
        $this->assertSame(0, PaymentLink::query()->count());
    }

    public function test_non_seller_cannot_access_payment_links(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('merchant.payment-links'))
            ->assertForbidden();
    }
}
