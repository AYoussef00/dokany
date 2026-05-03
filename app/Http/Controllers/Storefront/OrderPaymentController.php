<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StorefrontOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderPaymentController extends Controller
{
    use ResolvesStorefrontSeller;

    public function show(string $slug, StorefrontOrder $order): Response|RedirectResponse
    {
        $seller = $this->storefrontSellerFromSlug($slug);

        if ($order->user_id !== $seller->id) {
            abort(404);
        }

        if ($order->status === StorefrontOrder::STATUS_PAYMENT_SUBMITTED) {
            return redirect()
                ->route('storefront.show', ['slug' => $slug])
                ->with('payment_receipt_received', true);
        }

        return Inertia::render('public/OrderPayment', [
            'seller' => [
                'name' => $seller->name,
                'logo_url' => $seller->store_logo_url,
                'instapay_wallet' => $seller->instapay_wallet !== null && trim($seller->instapay_wallet) !== ''
                    ? trim($seller->instapay_wallet)
                    : null,
                'whatsapp_href' => $this->whatsappHref($seller->whatsapp_phone),
            ],
            'order' => [
                'uuid' => $order->uuid,
                'buyer_name' => $order->buyer_name,
                'buyer_phone' => $order->buyer_phone,
                'buyer_address' => $order->buyer_address,
                'buyer_maps_url' => $order->buyer_maps_url,
                'lines' => $order->lines,
                'subtotal' => (string) $order->subtotal,
                'currency_label_ar' => $order->currency_label_ar,
                'currency_label_en' => $order->currency_label_en,
                'status' => $order->status,
            ],
            'storefrontUrl' => $this->storefrontPath($slug),
            'orderPayPostPath' => $this->orderPayPath($slug, $order),
            'paymentInstructions' => config('dokany.storefront_buyer_payment.instructions'),
        ]);
    }

    public function update(Request $request, string $slug, StorefrontOrder $order): RedirectResponse
    {
        $seller = $this->storefrontSellerFromSlug($slug);

        if ($order->user_id !== $seller->id) {
            abort(404);
        }

        if ($order->status !== StorefrontOrder::STATUS_PENDING_PAYMENT) {
            return redirect()
                ->route('storefront.show', ['slug' => $slug])
                ->with('payment_receipt_received', true);
        }

        $request->validate([
            'payment_receipt' => ['required', 'image', 'max:5120'],
        ], [
            'payment_receipt.required' => 'يرجى إرفاق صورة إيصال التحويل.',
        ]);

        $path = $request->file('payment_receipt')->store('storefront-order-receipts', 'public');

        $order->payment_receipt_path = $path;
        $order->status = StorefrontOrder::STATUS_PAYMENT_SUBMITTED;
        $order->save();

        return redirect()
            ->route('storefront.show', ['slug' => $slug])
            ->with('payment_receipt_received', true);
    }

    private function storefrontPath(string $slug): string
    {
        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');

        return '/'.$prefix.'/'.$slug;
    }

    private function orderPayPath(string $slug, StorefrontOrder $order): string
    {
        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');

        return '/'.$prefix.'/'.$slug.'/order/'.$order->uuid.'/pay';
    }

    private function whatsappHref(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        return 'https://wa.me/'.$digits;
    }
}
