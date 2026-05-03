<?php

declare(strict_types=1);

namespace Tests\Feature\Merchant;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MerchantProductMutateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_update_product_text_only(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img = UploadedFile::fake()->image('orig.jpg');
        $this->actingAs($seller)->post(route('merchant.products.store'), [
            'name' => 'أصلي',
            'description' => 'وصف',
            'price' => '10',
            'images' => [$img],
        ]);

        $product = Product::query()->where('user_id', $seller->id)->first();
        $this->assertNotNull($product);

        $this->actingAs($seller)
            ->patch(route('merchant.products.update', $product), [
                'name' => 'محدّث',
                'description' => 'وصف جديد',
                'price' => '25.50',
            ])
            ->assertRedirect(route('merchant.products'));

        $product->refresh();
        $this->assertSame('محدّث', $product->name);
        $this->assertSame('وصف جديد', $product->description);
        $this->assertSame('25.50', $product->price);
        $this->assertCount(1, $product->images);
    }

    public function test_seller_can_remove_image_and_add_replacement(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img = UploadedFile::fake()->image('a.jpg');
        $this->actingAs($seller)->post(route('merchant.products.store'), [
            'name' => 'P',
            'description' => 'D',
            'price' => '1',
            'images' => [$img],
        ]);

        $product = Product::query()->where('user_id', $seller->id)->first();
        $this->assertNotNull($product);
        $imageId = $product->images->first()->id;
        $oldPath = $product->images->first()->path;

        $newImg = UploadedFile::fake()->image('b.jpg');

        $this->actingAs($seller)
            ->patch(route('merchant.products.update', $product), [
                'name' => 'P',
                'description' => 'D',
                'price' => '1',
                'remove_image_ids' => [$imageId],
                'images' => [$newImg],
            ])
            ->assertRedirect(route('merchant.products'));

        $product->refresh();
        $this->assertCount(1, $product->images);
        $this->assertNotSame($oldPath, $product->images->first()->path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->images->first()->path);
    }

    public function test_update_cannot_leave_product_with_zero_images(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img = UploadedFile::fake()->image('a.jpg');
        $this->actingAs($seller)->post(route('merchant.products.store'), [
            'name' => 'P',
            'description' => 'D',
            'price' => '1',
            'images' => [$img],
        ]);

        $product = Product::query()->where('user_id', $seller->id)->first();
        $this->assertNotNull($product);
        $imageId = $product->images->first()->id;

        $this->actingAs($seller)
            ->patch(route('merchant.products.update', $product), [
                'name' => 'P',
                'description' => 'D',
                'price' => '1',
                'remove_image_ids' => [$imageId],
            ])
            ->assertSessionHasErrors('images');
    }

    public function test_seller_cannot_update_another_sellers_product(): void
    {
        Storage::fake('public');

        $owner = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);
        $other = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img = UploadedFile::fake()->image('a.jpg');
        $this->actingAs($owner)->post(route('merchant.products.store'), [
            'name' => 'P',
            'description' => 'D',
            'price' => '1',
            'images' => [$img],
        ]);

        $product = Product::query()->where('user_id', $owner->id)->first();
        $this->assertNotNull($product);

        $this->actingAs($other)
            ->patch(route('merchant.products.update', $product), [
                'name' => 'X',
                'description' => 'Y',
                'price' => '2',
            ])
            ->assertForbidden();
    }

    public function test_seller_can_delete_own_product_and_storage_files(): void
    {
        Storage::fake('public');

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img = UploadedFile::fake()->image('a.jpg');
        $this->actingAs($seller)->post(route('merchant.products.store'), [
            'name' => 'P',
            'description' => 'D',
            'price' => '1',
            'images' => [$img],
        ]);

        $product = Product::query()->where('user_id', $seller->id)->first();
        $this->assertNotNull($product);
        $path = $product->images->first()->path;

        $this->actingAs($seller)
            ->delete(route('merchant.products.destroy', $product))
            ->assertRedirect(route('merchant.products'));

        $this->assertSame(0, Product::query()->count());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_seller_cannot_delete_another_sellers_product(): void
    {
        Storage::fake('public');

        $owner = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);
        $other = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $img = UploadedFile::fake()->image('a.jpg');
        $this->actingAs($owner)->post(route('merchant.products.store'), [
            'name' => 'P',
            'description' => 'D',
            'price' => '1',
            'images' => [$img],
        ]);

        $product = Product::query()->where('user_id', $owner->id)->first();
        $this->assertNotNull($product);

        $this->actingAs($other)
            ->delete(route('merchant.products.destroy', $product))
            ->assertForbidden();

        $this->assertSame(1, Product::query()->count());
    }
}
