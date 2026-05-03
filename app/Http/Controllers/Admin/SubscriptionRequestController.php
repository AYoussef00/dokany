<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $requests = User::query()
            ->where('role', User::ROLE_SELLER)
            ->where('merchant_subscription_status', User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW)
            ->orderByDesc('subscription_payment_reported_at')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'reported_at' => $user->subscription_payment_reported_at?->toIso8601String(),
                'payment_proof_url' => $user->subscription_payment_proof_path
                    ? asset('storage/'.$user->subscription_payment_proof_path)
                    : null,
            ]);

        return Inertia::render('admin/Requests', [
            'requests' => $requests,
            'subscriptionAmount' => (int) config('dokany.subscription.amount'),
            'subscriptionCurrencyEn' => (string) config('dokany.subscription.currency_label_en'),
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        $this->assertSellerPendingReview($user);

        $user->merchant_subscription_status = User::MERCHANT_SUBSCRIPTION_ACTIVE;
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Merchant approved and subscription activated.',
        ]);

        return back();
    }

    public function reject(User $user): RedirectResponse
    {
        $this->assertSellerPendingReview($user);

        $user->merchant_subscription_status = User::MERCHANT_SUBSCRIPTION_REJECTED;
        $user->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Subscription request rejected. This merchant cannot log in.',
        ]);

        return back();
    }

    private function assertSellerPendingReview(User $user): void
    {
        abort_unless($user->role === User::ROLE_SELLER, 404);
        abort_unless($user->merchant_subscription_status === User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW, 404);
    }
}
