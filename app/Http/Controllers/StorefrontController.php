<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $seller = User::query()
            ->where('store_slug', $slug)
            ->where('role', User::ROLE_SELLER)
            ->where('merchant_subscription_status', User::MERCHANT_SUBSCRIPTION_ACTIVE)
            ->first();

        if ($seller === null) {
            throw new NotFoundHttpException;
        }

        $this->recordStorefrontVisitOncePerSession($request, $seller);

        $products = $seller->products()
            ->with('images')
            ->latest()
            ->get();

        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');

        return Inertia::render('public/Storefront', [
            'seller' => [
                'store_slug' => $seller->store_slug,
                'name' => $seller->name,
                'logo_url' => $seller->store_logo_url,
                'whatsapp_phone' => $seller->whatsapp_phone,
                'whatsapp_href' => $this->whatsappHref($seller->whatsapp_phone),
                'hero_primary' => $seller->storefront_hero_primary,
                'hero_secondary' => $seller->storefront_hero_secondary,
                'hero_banner_urls' => collect($seller->storefront_hero_banner_paths ?? [])
                    ->filter(static function ($p) {
                        if (! is_string($p) || $p === '' || str_contains($p, '..')) {
                            return false;
                        }

                        return str_starts_with($p, 'store-hero-banners/');
                    })
                    ->map(static fn (string $p) => asset('storage/'.$p))
                    ->values()
                    ->all(),
                'social' => $this->storefrontSocialLinks($seller),
            ],
            'products' => $products->map(fn (Product $p) => $this->productPayload($p))->values(),
            'productCurrencyEn' => config('dokany.subscription.currency_label_en'),
            'productCurrencyAr' => config('dokany.subscription.currency_label'),
            'checkoutPath' => '/'.$prefix.'/'.$seller->store_slug.'/checkout',
        ]);
    }

    private function recordStorefrontVisitOncePerSession(Request $request, User $seller): void
    {
        $key = 'storefront_visit_tracked_'.$seller->id;

        if ($request->session()->get($key, false) === true) {
            return;
        }

        $request->session()->put($key, true);

        User::query()->whereKey($seller->id)->increment('storefront_visit_count');
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

    /**
     * @return array<string, string>|\stdClass
     */
    private function storefrontSocialLinks(User $seller): array|\stdClass
    {
        $map = [
            'facebook' => $seller->social_facebook_url,
            'instagram' => $seller->social_instagram_url,
            'x' => $seller->social_x_url,
            'youtube' => $seller->social_youtube_url,
            'tiktok' => $seller->social_tiktok_url,
        ];
        $out = [];
        foreach ($map as $key => $url) {
            if (is_string($url) && trim($url) !== '') {
                $out[$key] = trim($url);
            }
        }

        return $out === [] ? new \stdClass : $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'images' => $product->images->map(fn (ProductImage $img) => [
                'id' => $img->id,
                'url' => $img->url(),
            ])->values()->all(),
        ];
    }
}
