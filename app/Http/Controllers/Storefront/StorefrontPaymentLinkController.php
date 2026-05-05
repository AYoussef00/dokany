<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontPaymentLinkController extends Controller
{
    use ResolvesStorefrontSeller;

    public function show(string $slug, string $linkUuid): Response
    {
        $seller = $this->storefrontSellerFromSlug($slug);
        $link = $this->paymentLinkForSellerOrAbort($seller->id, $linkUuid);

        return Inertia::render('public/StorefrontPayLink', [
            'seller' => [
                'name' => $seller->name,
                'logo_url' => ($seller->store_logo_path !== null && $seller->store_logo_path !== '')
                    ? User::publicStorageUrl($seller->store_logo_path)
                    : null,
                'store_slug' => $seller->store_slug,
            ],
            'link' => [
                'uuid' => $link->uuid,
                'amount' => (string) $link->amount,
                'currency_label_ar' => $link->currency_label_ar,
                'currency_label_en' => $link->currency_label_en,
            ],
            'storefrontUrl' => $this->storefrontPath($slug),
            'payLinkPostPath' => $this->storefrontPath($slug).'/pay-link/'.$link->uuid,
        ]);
    }

    public function store(Request $request, string $slug, string $linkUuid): RedirectResponse
    {
        $seller = $this->storefrontSellerFromSlug($slug);
        $link = $this->paymentLinkForSellerOrAbort($seller->id, $linkUuid);

        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_phone' => ['required', 'string', 'max:64'],
            'buyer_address' => ['required', 'string', 'max:2000'],
            'buyer_maps_url' => ['nullable', 'string', 'max:2048'],
        ], [
            'buyer_name.required' => 'يرجى إدخال الاسم.',
            'buyer_phone.required' => 'يرجى إدخال رقم الهاتف.',
            'buyer_address.required' => 'يرجى إدخال العنوان.',
        ]);

        $amount = bcmul((string) $link->amount, '1', 2);
        $rawMaps = $validated['buyer_maps_url'] ?? null;

        $order = new StorefrontOrder([
            'uuid' => (string) Str::uuid(),
            'buyer_name' => $validated['buyer_name'],
            'buyer_phone' => $validated['buyer_phone'],
            'buyer_address' => $validated['buyer_address'],
            'buyer_maps_url' => is_string($rawMaps) && trim($rawMaps) !== '' ? trim($rawMaps) : null,
            'lines' => [
                [
                    'product_id' => 0,
                    'name' => 'دفع عبر رابط الدفع',
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'line_total' => $amount,
                ],
            ],
            'subtotal' => $amount,
            'currency_label_ar' => $link->currency_label_ar,
            'currency_label_en' => $link->currency_label_en,
            'status' => StorefrontOrder::STATUS_PENDING_PAYMENT,
        ]);
        $order->seller()->associate($seller);
        $order->paymentLink()->associate($link);
        $order->save();

        return redirect()->route('storefront.order.pay', [
            'slug' => $slug,
            'order' => $order,
        ]);
    }

    private function paymentLinkForSellerOrAbort(int $sellerId, string $linkUuid): PaymentLink
    {
        /** @var PaymentLink|null $link */
        $link = PaymentLink::query()
            ->where('uuid', $linkUuid)
            ->where('user_id', $sellerId)
            ->first();

        if ($link === null) {
            abort(404);
        }

        return $link;
    }

    private function storefrontPath(string $slug): string
    {
        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');

        return '/'.$prefix.'/'.$slug;
    }
}
