<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StorefrontOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    use ResolvesStorefrontSeller;

    public function create(string $slug): Response
    {
        $seller = $this->storefrontSellerFromSlug($slug);

        return Inertia::render('public/Checkout', [
            'seller' => [
                'store_slug' => $seller->store_slug,
                'name' => $seller->name,
                'logo_url' => ($seller->store_logo_path !== null && $seller->store_logo_path !== '')
                    ? User::publicStorageUrl($seller->store_logo_path)
                    : null,
                'instapay_wallet' => $seller->instapay_wallet !== null && trim($seller->instapay_wallet) !== ''
                    ? trim($seller->instapay_wallet)
                    : null,
                'whatsapp_href' => $this->whatsappHref($seller->whatsapp_phone),
            ],
            'storefrontUrl' => $this->storefrontPath($slug),
            'checkoutPostPath' => $this->storefrontPath($slug).'/checkout',
            'productCurrencyAr' => config('dokany.subscription.currency_label'),
            'paymentInstructions' => config('dokany.storefront_buyer_payment.instructions'),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $seller = $this->storefrontSellerFromSlug($slug);

        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'buyer_phone' => ['required', 'string', 'max:64'],
            'buyer_address' => ['required', 'string', 'max:2000'],
            'buyer_maps_url' => ['nullable', 'string', 'max:2048'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ], [
            'buyer_name.required' => 'يرجى إدخال الاسم.',
            'buyer_phone.required' => 'يرجى إدخال رقم الهاتف.',
            'buyer_address.required' => 'يرجى إدخال العنوان.',
            'lines.required' => 'السلة فارغة.',
        ]);

        $linesOut = [];
        $subtotal = '0';

        foreach ($validated['lines'] as $line) {
            $product = Product::query()
                ->where('user_id', $seller->id)
                ->where('id', $line['product_id'])
                ->first();

            if ($product === null) {
                return back()
                    ->withErrors(['lines' => 'أحد المنتجات غير متاح. حدّث الصفحة وحاول مجدداً.'])
                    ->withInput();
            }

            $qty = $line['quantity'];
            $unit = $product->price;
            $lineTotal = bcmul((string) $unit, (string) $qty, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);

            $linesOut[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'quantity' => $qty,
                'unit_price' => (string) $unit,
                'line_total' => $lineTotal,
            ];
        }

        $rawMapsUrl = $validated['buyer_maps_url'] ?? null;
        $order = new StorefrontOrder([
            'uuid' => (string) Str::uuid(),
            'buyer_name' => $validated['buyer_name'],
            'buyer_phone' => $validated['buyer_phone'],
            'buyer_address' => $validated['buyer_address'],
            'buyer_maps_url' => is_string($rawMapsUrl) && trim($rawMapsUrl) !== ''
                ? trim($rawMapsUrl)
                : null,
            'lines' => $linesOut,
            'subtotal' => $subtotal,
            'currency_label_ar' => (string) config('dokany.subscription.currency_label'),
            'currency_label_en' => (string) config('dokany.subscription.currency_label_en'),
            'status' => StorefrontOrder::STATUS_PENDING_PAYMENT,
        ]);
        $order->seller()->associate($seller);
        $order->save();

        return redirect()->route('storefront.order.pay', [
            'slug' => $slug,
            'order' => $order,
        ]);
    }

    private function storefrontPath(string $slug): string
    {
        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');

        return '/'.$prefix.'/'.$slug;
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
