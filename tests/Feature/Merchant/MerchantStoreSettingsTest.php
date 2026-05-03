<?php

declare(strict_types=1);

namespace Tests\Feature\Merchant;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class MerchantStoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_update_store_settings(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'name' => 'Old Name',
            'store_slug' => 'old-slug',
            'email' => 'seller@example.com',
            'phone' => '01000000001',
            'whatsapp_phone' => '01000000002',
            'instapay_wallet' => '01000000003',
        ]);

        $logo = UploadedFile::fake()->image('logo.jpg', 200, 200);

        $this->actingAs($seller)
            ->patch(route('merchant.store-settings.update'), [
                'name' => 'New Store',
                'store_slug' => 'new-store-slug',
                'email' => 'new@example.com',
                'phone' => '01099999999',
                'whatsapp_phone' => '01088888888',
                'instapay_wallet' => '01077777777',
                'storefront_hero_primary' => 'نص ترحيب مخصص',
                'storefront_hero_secondary' => 'سطر ثانٍ',
                'social_facebook_url' => 'https://facebook.com/x',
                'social_instagram_url' => 'https://instagram.com/x',
                'social_x_url' => 'https://x.com/x',
                'social_youtube_url' => 'https://youtube.com/channel/test',
                'social_tiktok_url' => 'https://tiktok.com/@test',
                'store_logo' => $logo,
            ])
            ->assertRedirect(route('merchant.store-settings.edit'));

        $seller->refresh();
        $this->assertSame('New Store', $seller->name);
        $this->assertSame('new-store-slug', $seller->store_slug);
        $this->assertSame('new@example.com', $seller->email);
        $this->assertSame('نص ترحيب مخصص', $seller->storefront_hero_primary);
        $this->assertSame('سطر ثانٍ', $seller->storefront_hero_secondary);
        $this->assertSame('https://facebook.com/x', $seller->social_facebook_url);
        $this->assertSame('https://instagram.com/x', $seller->social_instagram_url);
        $this->assertSame('https://x.com/x', $seller->social_x_url);
        $this->assertSame('https://youtube.com/channel/test', $seller->social_youtube_url);
        $this->assertSame('https://tiktok.com/@test', $seller->social_tiktok_url);
        $this->assertNotNull($seller->store_logo_path);
        Storage::disk('public')->assertExists($seller->store_logo_path);
    }

    public function test_seller_can_upload_hero_banners(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'name' => 'Banner Store',
            'store_slug' => 'banner-shop',
            'email' => 'banner@example.com',
            'phone' => '01000000001',
            'whatsapp_phone' => '01000000002',
            'instapay_wallet' => '01000000003',
        ]);

        $b1 = UploadedFile::fake()->image('b1.jpg', 900, 600);
        $b2 = UploadedFile::fake()->image('b2.png', 600, 600);

        $this->actingAs($seller)
            ->post(route('merchant.store-settings.update'), [
                '_method' => 'PATCH',
                'name' => $seller->name,
                'store_slug' => $seller->store_slug,
                'email' => $seller->email,
                'phone' => $seller->phone,
                'whatsapp_phone' => $seller->whatsapp_phone,
                'instapay_wallet' => $seller->instapay_wallet,
                'storefront_hero_primary' => '',
                'storefront_hero_secondary' => '',
                'social_facebook_url' => '',
                'social_instagram_url' => '',
                'social_x_url' => '',
                'social_youtube_url' => '',
                'social_tiktok_url' => '',
                'hero_banner_images' => [$b1, $b2],
            ])
            ->assertRedirect(route('merchant.store-settings.edit'));

        $seller->refresh();
        $paths = $seller->storefront_hero_banner_paths;
        $this->assertIsArray($paths);
        $this->assertCount(2, $paths);
        foreach ($paths as $p) {
            Storage::disk('public')->assertExists($p);
        }

        $prefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');
        $this->get('/'.$prefix.'/banner-shop')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('seller.hero_banner_urls', 2));
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'store_slug' => 'my-shop',
        ]);

        $this->actingAs($seller)
            ->patch(route('merchant.store-settings.update'), [
                'name' => $seller->name,
                'store_slug' => 'admin',
                'email' => $seller->email,
                'phone' => $seller->phone,
                'whatsapp_phone' => $seller->whatsapp_phone,
                'instapay_wallet' => $seller->instapay_wallet,
            ])
            ->assertSessionHasErrors('store_slug');
    }

    public function test_admin_cannot_access_merchant_store_settings(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $this->actingAs($admin)
            ->get(route('merchant.store-settings.edit'))
            ->assertForbidden();
    }
}
