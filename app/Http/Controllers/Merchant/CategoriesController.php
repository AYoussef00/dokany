<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchant;

use App\Enums\StorefrontProductCategory;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $hiddenValues = DB::table('merchant_hidden_categories')
            ->where('user_id', $user->id)
            ->pluck('category')
            ->all();

        $categories = collect(StorefrontProductCategory::cases())
            ->reject(static fn (StorefrontProductCategory $c): bool => in_array($c->value, $hiddenValues, true))
            ->map(static function (StorefrontProductCategory $c) use ($user): array {
                return [
                    'value' => $c->value,
                    'label' => $c->labelAr(),
                    'products_count' => $user->products()->where('storefront_category', $c->value)->count(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('merchant/Categories', [
            'categories' => $categories,
            'addableCategories' => collect(StorefrontProductCategory::cases())
                ->filter(static fn (StorefrontProductCategory $c): bool => in_array($c->value, $hiddenValues, true))
                ->map(static fn (StorefrontProductCategory $c): array => [
                    'value' => $c->value,
                    'label' => $c->labelAr(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'category' => ['required', Rule::enum(StorefrontProductCategory::class)],
        ]);

        DB::table('merchant_hidden_categories')
            ->where('user_id', $user->id)
            ->where('category', $validated['category'])
            ->delete();

        return redirect()->route('merchant.categories')
            ->with('success', 'تمت إضافة الصنف بنجاح.');
    }

    public function destroy(Request $request, string $category): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $categoryEnum = StorefrontProductCategory::tryFrom($category);
        if ($categoryEnum === null) {
            abort(404);
        }

        $allCases = collect(StorefrontProductCategory::cases());
        $hiddenValues = DB::table('merchant_hidden_categories')
            ->where('user_id', $user->id)
            ->pluck('category')
            ->all();

        $activeCategories = $allCases
            ->reject(static fn (StorefrontProductCategory $c): bool => in_array($c->value, $hiddenValues, true))
            ->values();

        if ($activeCategories->count() <= 1) {
            return redirect()->route('merchant.categories')
                ->with('error', 'لا يمكن حذف آخر صنف متاح.');
        }

        $fallback = $activeCategories
            ->first(static fn (StorefrontProductCategory $c): bool => $c->value !== $categoryEnum->value);

        if (! $fallback instanceof StorefrontProductCategory) {
            return redirect()->route('merchant.categories')
                ->with('error', 'تعذر تحديد صنف بديل.');
        }

        DB::transaction(function () use ($user, $categoryEnum, $fallback): void {
            Product::query()
                ->where('user_id', $user->id)
                ->where('storefront_category', $categoryEnum->value)
                ->update(['storefront_category' => $fallback->value]);

            DB::table('merchant_hidden_categories')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'category' => $categoryEnum->value,
                ],
                [
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        });

        return redirect()->route('merchant.categories')
            ->with('success', 'تم حذف الصنف ونقل منتجاته إلى صنف بديل.');
    }
}
