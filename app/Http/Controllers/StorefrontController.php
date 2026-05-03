<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $ttl = (int) config('dokany.storefront_catalog_cache_ttl', 0);
        if ($ttl > 0) {
            $cacheKey = $this->storefrontCatalogCacheKey($seller);

            return Inertia::render('public/Storefront', Cache::remember($cacheKey, $ttl, function () use ($seller) {
                return $this->storefrontPagePayload($seller);
            }));
        }

        return Inertia::render('public/Storefront', $this->storefrontPagePayload($seller));
    }

    /**
     * @return array<string, mixed>
     */
    private function storefrontPagePayload(User $seller): array
    {
        $prefix = trim((string) config('dokany.storefront_path_prefix', 'shop'), '/');

        $products = $seller->products()
            ->with('images')
            ->latest()
            ->get();

        return [
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
        ];
    }

    private function storefrontCatalogCacheKey(User $seller): string
    {
        $productMax = Product::query()->where('user_id', $seller->id)->max('updated_at');
        $imageMax = ProductImage::query()
            ->whereIn('product_id', Product::query()->where('user_id', $seller->id)->select('id'))
            ->max('updated_at');

        $bannerPaths = $seller->storefront_hero_banner_paths ?? [];
        $bannerSig = is_array($bannerPaths) ? (json_encode($bannerPaths) ?: '') : '';

        $parts = [
            'v1',
            (string) $seller->id,
            (string) $seller->merchant_subscription_status,
            (string) ($seller->updated_at?->timestamp ?? 0),
            (string) $seller->store_logo_path,
            $bannerSig,
            (string) ($seller->storefront_hero_primary ?? ''),
            (string) ($seller->storefront_hero_secondary ?? ''),
            (string) ($productMax ?? ''),
            (string) ($imageMax ?? ''),
        ];

        return 'storefront.catalog.'.hash('xxh128', implode("\0", $parts));
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
