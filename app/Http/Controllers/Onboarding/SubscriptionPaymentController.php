<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPaymentController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('onboarding/SubscriptionPayment', [
            'subscriptionAmount' => config('dokany.subscription.amount'),
            'subscriptionCurrency' => config('dokany.subscription.currency_label'),
            'platformInstapayNumber' => config('dokany.subscription.instapay_wallet'),
            'paymentInstructions' => config('dokany.subscription.instructions'),
            'merchantInstapay' => $user->instapay_wallet,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_proof' => ['required', 'image', 'max:5120'],
        ], [
            'payment_proof.required' => 'يجب إرفاق صورة إثبات التحويل.',
        ]);

        $user = $request->user();

        $path = $request->file('payment_proof')->store('subscription-payment-proofs', 'public');
        $user->subscription_payment_proof_path = $path;

        $user->subscription_payment_reported_at = now();
        $user->merchant_subscription_status = User::MERCHANT_SUBSCRIPTION_PENDING_REVIEW;
        $user->save();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'status',
                'تم استلام إثبات الدفع. سيظهر طلبك للإدارة للمراجعة، ويمكنك تسجيل الدخول بعد الموافقة على الاشتراك.',
            );
    }
}
