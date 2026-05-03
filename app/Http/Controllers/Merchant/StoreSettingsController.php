<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->isSeller(), 403);

        return Inertia::render('merchant/StoreSettings', [
            'seller' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'whatsapp_phone' => $user->whatsapp_phone,
                'instapay_wallet' => $user->instapay_wallet,
                'store_slug' => $user->store_slug,
                'store_logo_url' => $user->store_logo_url,
                'public_store_url' => $user->storefrontPublicUrl(),
                'storefront_hero_primary' => $user->storefront_hero_primary,
                'storefront_hero_secondary' => $user->storefront_hero_secondary,
                'hero_banners' => $this->heroBannersPayload($user),
                'social_facebook_url' => $user->social_facebook_url,
                'social_instagram_url' => $user->social_instagram_url,
                'social_x_url' => $user->social_x_url,
                'social_youtube_url' => $user->social_youtube_url,
                'social_tiktok_url' => $user->social_tiktok_url,
            ],
            'storefrontPrefix' => trim((string) config('dokany.storefront_path_prefix', 'shop'), '/'),
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->isSeller(), 403);

        $reserved = array_map(
            static fn (string $s) => strtolower($s),
            config('dokany.reserved_store_slugs', []),
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'store_slug' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('users', 'store_slug')->ignore($user->id),
                Rule::notIn($reserved),
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:32'],
            'whatsapp_phone' => ['required', 'string', 'max:32'],
            'instapay_wallet' => ['required', 'string', 'max:64'],
            'store_logo' => ['nullable', 'image', 'max:10240'],
            'storefront_hero_primary' => ['nullable', 'string', 'max:5000'],
            'storefront_hero_secondary' => ['nullable', 'string', 'max:5000'],
            'social_facebook_url' => ['nullable', 'string', 'max:512'],
            'social_instagram_url' => ['nullable', 'string', 'max:512'],
            'social_x_url' => ['nullable', 'string', 'max:512'],
            'social_youtube_url' => ['nullable', 'string', 'max:512'],
            'social_tiktok_url' => ['nullable', 'string', 'max:512'],
            'hero_banner_remove_paths' => ['nullable', 'array', 'max:24'],
            'hero_banner_remove_paths.*' => ['string', 'max:512'],
            'hero_banner_images' => ['nullable', 'array', 'max:8'],
            'hero_banner_images.*' => ['file', 'image', 'max:10240'],
        ]);

        foreach (['storefront_hero_primary', 'storefront_hero_secondary'] as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }
            $trimmed = trim((string) $validated[$field]);
            $validated[$field] = $trimmed === '' ? null : $trimmed;
        }

        $socialUrlFields = [
            'social_facebook_url',
            'social_instagram_url',
            'social_x_url',
            'social_youtube_url',
            'social_tiktok_url',
        ];
        foreach ($socialUrlFields as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }
            $raw = trim((string) $validated[$field]);
            if ($raw === '') {
                $validated[$field] = null;

                continue;
            }
            if (! preg_match('#^https?://#i', $raw)) {
                $raw = 'https://'.$raw;
            }
            if (filter_var($raw, FILTER_VALIDATE_URL) === false) {
                throw ValidationException::withMessages([
                    $field => 'يرجى إدخال رابط صالح (مثال: https://instagram.com/ ...) ',
                ]);
            }
            $validated[$field] = $raw;
        }

        if ($request->hasFile('store_logo')) {
            $file = $request->file('store_logo');
            if ($file !== null && $file->isValid()) {
                if ($user->store_logo_path) {
                    Storage::disk('public')->delete($user->store_logo_path);
                }
                $validated['store_logo_path'] = $file->store('store-logos', 'public');
            }
        }

        unset($validated['store_logo']);

        $this->syncStorefrontHeroBanners($request, $user);

        unset($validated['hero_banner_remove_paths'], $validated['hero_banner_images']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('merchant.store-settings.edit')
            ->with('success', 'تم حفظ إعدادات المتجر.');
    }

    /**
     * @return list<array{path: string, url: string}>
     */
    private function heroBannersPayload(User $user): array
    {
        $paths = $user->storefront_hero_banner_paths;
        if (! is_array($paths)) {
            return [];
        }
        $out = [];
        foreach ($paths as $p) {
            if (is_string($p) && $p !== '' && $this->isAllowedStoreBannerPath($p)) {
                $out[] = [
                    'path' => $p,
                    'url' => asset('storage/'.$p),
                ];
            }
        }

        return $out;
    }

    private function isAllowedStoreBannerPath(string $path): bool
    {
        if (str_contains($path, '..')) {
            return false;
        }

        return str_starts_with($path, 'store-hero-banners/');
    }

    private function syncStorefrontHeroBanners(Request $request, User $user): void
    {
        $current = $user->storefront_hero_banner_paths;
        if (! is_array($current)) {
            $current = [];
        }
        $current = array_values(array_filter(
            $current,
            static fn ($p) => is_string($p) && $p !== '',
        ));

        $removeRaw = $request->input('hero_banner_remove_paths', []);
        if (! is_array($removeRaw)) {
            $removeRaw = [];
        }
        $toRemove = [];
        foreach ($removeRaw as $item) {
            if (! is_string($item) || $item === '') {
                continue;
            }
            if (! $this->isAllowedStoreBannerPath($item)) {
                continue;
            }
            if (in_array($item, $current, true)) {
                $toRemove[] = $item;
            }
        }
        $toRemove = array_values(array_unique($toRemove));

        foreach ($toRemove as $path) {
            Storage::disk('public')->delete($path);
        }

        $kept = array_values(array_diff($current, $toRemove));

        $uploads = $request->file('hero_banner_images', []);
        if (! is_array($uploads)) {
            $uploads = [];
        }
        $newPaths = [];
        foreach ($uploads as $file) {
            if ($file === null || ! $file->isValid()) {
                continue;
            }
            $newPaths[] = $file->store('store-hero-banners', 'public');
        }

        $merged = array_merge($kept, $newPaths);
        if (count($merged) > 8) {
            foreach ($newPaths as $p) {
                Storage::disk('public')->delete($p);
            }
            throw ValidationException::withMessages([
                'hero_banner_images' => 'يمكنك رفع 8 صور كحد أقصى للبانر المتحرك.',
            ]);
        }

        $user->storefront_hero_banner_paths = $merged === [] ? null : $merged;
    }
}
