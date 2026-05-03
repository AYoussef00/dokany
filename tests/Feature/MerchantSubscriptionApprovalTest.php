<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantSubscriptionApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_inactive_can_authenticate(): void
    {
        $user = User::factory()->seller()->create();

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_seller_pending_review_cannot_authenticate(): void
    {
        $user = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW,
        ]);
    }

    public function test_seller_rejected_cannot_authenticate(): void
    {
        $user = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_REJECTED,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_seller_active_can_authenticate(): void
    {
        $user = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_subscription_payment_submit_sets_pending_review_and_logs_out(): void
    {
        Storage::fake('public');

        $user = User::factory()->seller()->create([
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('proof.jpg');

        $response = $this->actingAs($user)->post(route('onboarding.subscription-payment.store'), [
            'payment_proof' => $file,
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));

        $user->refresh();
        $this->assertSame(User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW, $user->merchant_subscription_status);
        $this->assertNotNull($user->subscription_payment_proof_path);
    }

    public function test_admin_can_list_and_approve_pending_seller(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $pending = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW,
            'subscription_payment_reported_at' => now(),
            'subscription_payment_proof_path' => 'subscription-payment-proofs/x.jpg',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('requests', 1)
                ->where('requests.0.id', $pending->id)
                ->has('subscriptionAmount')
                ->has('subscriptionCurrencyEn'));

        $this->actingAs($admin)
            ->get(route('admin.sellers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('sellers', 0));

        $this->actingAs($admin)
            ->post(route('admin.requests.approve', $pending))
            ->assertRedirect();

        $this->assertSame(User::MERCHANT_SUBSCRIPTION_ACTIVE, $pending->fresh()->merchant_subscription_status);

        $this->actingAs($admin)
            ->get(route('admin.sellers'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sellers', 1)
                ->where('sellers.0.id', $pending->id)
                ->where('sellers.0.access_expired', false)
                ->has('subscriptionCurrencyEn')
                ->has('platformSubscriptionAmount'));

        $this->actingAs($admin)->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $pending->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($pending->fresh());
    }

    public function test_admin_can_reject_pending_seller(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
        ]);

        $pending = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW,
            'subscription_payment_reported_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.reject', $pending))
            ->assertRedirect();

        $this->assertSame(User::MERCHANT_SUBSCRIPTION_REJECTED, $pending->fresh()->merchant_subscription_status);
    }

    public function test_active_seller_beyond_access_window_cannot_log_in(): void
    {
        $user = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
            'created_at' => now()->subDays(45),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_expired_active_seller_is_logged_out_when_visiting_dashboard(): void
    {
        $user = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
            'created_at' => now()->subDays(45),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_seller_within_access_window_can_access_dashboard(): void
    {
        $user = User::factory()->seller()->create([
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'email_verified_at' => now(),
            'created_at' => now()->subDays(5),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
