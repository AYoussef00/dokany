<?php

declare(strict_types=1);

namespace Tests\Feature\Merchant;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MerchantProductStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_create_product_with_multiple_images(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img1 = UploadedFile::fake()->image('a.jpg', 640, 480);
        $img2 = UploadedFile::fake()->image('b.png', 400, 400);

        $this->actingAs($seller)
            ->post(route('merchant.products.store'), [
                'name' => 'منتج تجريبي',
                'description' => 'وصف تفصيلي للمنتج المعروض للبيع.',
                'price' => '149.99',
                'storefront_category' => 'new_in',
                'images' => [$img1, $img2],
            ])
            ->assertRedirect(route('merchant.products'));

        $product = Product::query()->where('user_id', $seller->id)->first();
        $this->assertNotNull($product);
        $this->assertSame('منتج تجريبي', $product->name);
        $this->assertSame('149.99', $product->price);
        $this->assertSame('new_in', $product->storefront_category->value);
        $this->assertCount(2, $product->images);

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_store_requires_at_least_one_image(): void
    {
        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $this->actingAs($seller)
            ->post(route('merchant.products.store'), [
                'name' => 'بدون صور',
                'description' => 'وصف',
                'price' => '10',
                'storefront_category' => 'new_in',
                'images' => [],
            ])
            ->assertSessionHasErrors('images');
    }

    public function test_admin_cannot_create_merchant_product_via_merchant_route(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img = UploadedFile::fake()->image('x.jpg');

        $this->actingAs($admin)
            ->post(route('merchant.products.store'), [
                'name' => 'X',
                'description' => 'Y',
                'price' => '1',
                'storefront_category' => 'new_in',
                'images' => [$img],
            ])
            ->assertForbidden();

        $this->assertSame(0, Product::query()->count());
    }
}
