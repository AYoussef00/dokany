<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SellerController extends Controller
{
    use PasswordValidationRules, ProfileValidationRules;

    public function index(): Response
    {
        $sellers = User::query()
            ->where('role', User::ROLE_SELLER)
            ->where('merchant_subscription_status', User::MERCHANT_SUBSCRIPTION_ACTIVE)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'whatsapp_phone' => $user->whatsapp_phone,
                'instapay_wallet' => $user->instapay_wallet,
                'subscription_paid_amount' => $user->subscription_paid_amount !== null
                    ? (string) $user->subscription_paid_amount
                    : null,
                'merchant_access_months' => $user->merchant_access_months,
                'created_at' => $user->created_at?->toIso8601String(),
                'subscription_payment_reported_at' => $user->subscription_payment_reported_at?->toIso8601String(),
                'access_ends_at' => $user->merchantAccessEndsAt()?->toIso8601String(),
                'access_expired' => $user->merchantAccessExpired(),
            ]);

        return Inertia::render('admin/Sellers', [
            'sellers' => $sellers,
            'subscriptionCurrencyEn' => (string) config('dokany.subscription.currency_label_en'),
            'platformSubscriptionAmount' => (int) config('dokany.subscription.amount'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'subscription_paid_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'merchant_access_months' => ['required', 'integer', 'min:1', 'max:120'],
            'instapay_wallet' => ['required', 'string', 'max:64'],
            'whatsapp_phone' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string', 'max:2000'],
            'store_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $logoPath = null;
        if ($request->hasFile('store_logo')) {
            $logoPath = $request->file('store_logo')->store('store-logos', 'public');
        }

        User::create([
            'name' => $request->string('name')->value(),
            'email' => $request->string('email')->value(),
            'password' => $request->input('password'),
            'role' => User::ROLE_SELLER,
            'merchant_subscription_status' => User::MERCHANT_SUBSCRIPTION_ACTIVE,
            'subscription_paid_amount' => $request->input('subscription_paid_amount'),
            'merchant_access_months' => $request->integer('merchant_access_months'),
            'email_verified_at' => now(),
            'store_logo_path' => $logoPath,
            'instapay_wallet' => $request->string('instapay_wallet')->value(),
            'whatsapp_phone' => $request->string('whatsapp_phone')->value(),
            'phone' => $request->string('phone')->value(),
            'address' => $request->string('address')->value(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Merchant created and can log in immediately.',
        ]);

        return redirect()->route('admin.sellers');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === User::ROLE_SELLER, 404);

        if ($user->store_logo_path) {
            Storage::disk('public')->delete($user->store_logo_path);
        }

        if ($user->subscription_payment_proof_path) {
            Storage::disk('public')->delete($user->subscription_payment_proof_path);
        }

        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Merchant deleted. All sessions ended; they can no longer sign in.',
        ]);

        return redirect()->route('admin.sellers');
    }
}
