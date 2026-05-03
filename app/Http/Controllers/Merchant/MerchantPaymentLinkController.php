<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MerchantPaymentLinkController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $seller */
        $seller = $request->user();

        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');
        $slug = $seller->store_slug;

        $links = PaymentLink::query()
            ->where('user_id', $seller->id)
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('merchant/PaymentLinks', [
            'storeSlug' => $slug,
            'pathPrefix' => $prefix,
            'canPublishLinks' => $slug !== null && $slug !== '',
            'links' => $links->map(fn (PaymentLink $l) => [
                'uuid' => $l->uuid,
                'amount' => (string) $l->amount,
                'currency_label_ar' => $l->currency_label_ar,
                'created_at' => $l->created_at?->toIso8601String(),
                'pay_path' => ($slug !== null && $slug !== '')
                    ? '/'.$prefix.'/'.$slug.'/pay-link/'.$l->uuid
                    : null,
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $seller */
        $seller = $request->user();

        if ($seller->store_slug === null || $seller->store_slug === '') {
            return redirect()
                ->route('merchant.payment-links')
                ->with('error', 'حدّد رابط متجرك في الإعدادات أولاً لاستخدام روابط الدفع.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ], [
            'amount.required' => 'أدخل المبلغ.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر.',
        ]);

        PaymentLink::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $seller->id,
            'amount' => $validated['amount'],
            'currency_label_ar' => (string) config('dokany.subscription.currency_label'),
            'currency_label_en' => (string) config('dokany.subscription.currency_label_en'),
        ]);

        return redirect()
            ->route('merchant.payment-links')
            ->with('success', 'تم إنشاء رابط الدفع. انسخ الرابط وأرسله للعميل.');
    }
}
