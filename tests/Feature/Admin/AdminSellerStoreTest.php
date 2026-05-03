<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSellerStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_seller_from_sellers_page(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $logo = UploadedFile::fake()->image('logo.png', 120, 120);

        $this->actingAs($admin)
            ->post(route('admin.sellers.store'), [
                'name' => 'متجر يدوي',
                'email' => 'manual-merchant@example.com',
                'password' => 'password123!',
                'password_confirmation' => 'password123!',
                'subscription_paid_amount' => '750.5',
                'merchant_access_months' => 3,
                'instapay_wallet' => '01001234567',
                'whatsapp_phone' => '01001234568',
                'phone' => '01001234569',
                'address' => 'Cairo, Egypt',
                'store_logo' => $logo,
            ])
            ->assertRedirect(route('admin.sellers'));

        $this->assertDatabaseHas('users', [
            'email' => 'manual-merchant@example.com',
            'role' => User::ROLE_SELLER,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $seller = User::query()->where('email', 'manual-merchant@example.com')->first();
        $this->assertNotNull($seller);
        $this->assertNotNull($seller->email_verified_at);
        $this->assertNotNull($seller->store_logo_path);
        $this->assertSame(3, $seller->merchant_access_months);
        $this->assertSame('750.50', $seller->subscription_paid_amount);
        $this->assertSame(
            Carbon::parse($seller->created_at)->addMonths(3)->format('Y-m-d H:i'),
            $seller->merchantAccessEndsAt()->format('Y-m-d H:i'),
        );

        $this->actingAs($admin)
            ->get(route('admin.sellers'))
            ->assertOk();

        $this->actingAs($admin)->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => 'manual-merchant@example.com',
            'password' => 'password123!',
        ]);

        $this->assertAuthenticatedAs($seller->fresh());
    }

    public function test_non_admin_cannot_create_seller_via_store_route(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('admin.sellers.store'), [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'password123!',
                'password_confirmation' => 'password123!',
                'subscription_paid_amount' => '100',
                'merchant_access_months' => 1,
                'instapay_wallet' => '01001234567',
                'whatsapp_phone' => '01001234568',
                'phone' => '01001234569',
                'address' => 'Address line',
            ])
            ->assertForbidden();
    }
}
