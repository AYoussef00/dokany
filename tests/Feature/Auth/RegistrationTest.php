<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'متجر تجريبي',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'instapay_wallet' => '01001234567',
            'whatsapp_phone' => '01001234567',
            'phone' => '01001234567',
            'address' => 'القاهرة، مصر',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.subscription-payment', absolute: false));
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => User::ROLE_SELLER,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_INACTIVE,
        ]);

        $user = User::query()->where('email', 'test@example.com')->first();
        $this->assertNotNull($user?->store_slug);
        $this->assertNotSame('', $user->store_slug);
    }
}
