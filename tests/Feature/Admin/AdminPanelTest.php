<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_admin_requests(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests'))
            ->assertOk();
    }

    public function test_seller_cannot_open_admin_requests(): void
    {
        $seller = User::factory()->create([
            'role' => User::ROLE_SELLER,
        ]);

        $this->actingAs($seller)
            ->get(route('admin.requests'))
            ->assertForbidden();
    }

    public function test_guest_cannot_open_admin_requests(): void
    {
        $this->get(route('admin.requests'))
            ->assertRedirect(route('login'));
    }
}
