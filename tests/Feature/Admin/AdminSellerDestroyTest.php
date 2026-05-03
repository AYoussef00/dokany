<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSellerDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_seller(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $sellerId = $seller->id;

        $this->actingAs($admin)
            ->delete(route('admin.sellers.destroy', $seller))
            ->assertRedirect(route('admin.sellers'));

        $this->assertDatabaseMissing('users', ['id' => $sellerId]);
    }

    public function test_admin_cannot_delete_non_seller_via_destroy_route(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $buyer = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.sellers.destroy', $buyer))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $buyer->id]);
    }

    public function test_non_admin_cannot_delete_seller(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'email_verified_at' => now(),
        ]);

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('admin.sellers.destroy', $seller))
            ->assertForbidden();
    }

    public function test_deleting_seller_removes_database_sessions_and_prevents_login(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $seller = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $sellerId = $seller->id;
        $sellerEmail = $seller->email;

        DB::table('sessions')->insert([
            'id' => 'test-session-merchant-deleted',
            'user_id' => $sellerId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);

        $this->assertDatabaseHas('sessions', ['user_id' => $sellerId]);

        $this->actingAs($admin)
            ->delete(route('admin.sellers.destroy', $seller))
            ->assertRedirect(route('admin.sellers'));

        $this->assertDatabaseMissing('sessions', ['user_id' => $sellerId]);
        $this->assertDatabaseMissing('users', ['id' => $sellerId]);

        $this->actingAs($admin)->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $sellerEmail,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
