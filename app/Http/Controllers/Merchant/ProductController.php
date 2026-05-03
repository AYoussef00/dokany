<?php

declare(strict_types=1);

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = $request->user()
            ->products()
            ->with('images')
            ->latest()
            ->get();

        return Inertia::render('merchant/Products', [
            'products' => $products->map(fn (Product $p) => $this->productPayload($p))->values(),
            'productCurrencyAr' => config('dokany.subscription.currency_label'),
            'productCurrencyEn' => config('dokany.subscription.currency_label_en'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['image', 'max:10240'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $product = $request->user()->products()->create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
            ]);

            foreach ($request->file('images', []) as $index => $file) {
                $path = $file->store('product-images', 'public');
                $product->images()->create([
                    'path' => $path,
                    'sort_order' => $index,
                ]);
            }
        });

        return redirect()->route('merchant.products')
            ->with('success', 'تم إضافة المنتج بنجاح.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeSellerProduct($request, $product);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'remove_image_ids' => ['sometimes', 'array'],
            'remove_image_ids.*' => ['integer'],
            'images' => ['sometimes', 'array'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $removeIds = array_values(array_unique(array_map('intval', $validated['remove_image_ids'] ?? [])));
        $ownedIds = $product->images()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($removeIds as $removeId) {
            if (! in_array($removeId, $ownedIds, true)) {
                throw ValidationException::withMessages([
                    'remove_image_ids' => 'صورة غير صالحة.',
                ]);
            }
        }

        $newFiles = $request->file('images', []);
        $remainingAfterRemove = count($ownedIds) - count($removeIds);
        $finalCount = $remainingAfterRemove + count($newFiles);

        if ($finalCount < 1) {
            throw ValidationException::withMessages([
                'images' => 'يجب أن يبقى للمنتج صورة واحدة على الأقل.',
            ]);
        }

        if ($finalCount > 10) {
            throw ValidationException::withMessages([
                'images' => 'لا يمكن أن يتجاوز عدد صور المنتج 10.',
            ]);
        }

        DB::transaction(function () use ($product, $validated, $removeIds, $newFiles): void {
            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
            ]);

            if ($removeIds !== []) {
                $toDelete = $product->images()->whereIn('id', $removeIds)->get();
                foreach ($toDelete as $image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                }
            }

            $product->refresh();

            $maxOrder = (int) ($product->images()->max('sort_order') ?? -1);

            foreach ($newFiles as $index => $file) {
                $path = $file->store('product-images', 'public');
                $product->images()->create([
                    'path' => $path,
                    'sort_order' => $maxOrder + 1 + $index,
                ]);
            }
        });

        return redirect()->route('merchant.products')
            ->with('success', 'تم تحديث المنتج بنجاح.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeSellerProduct($request, $product);

        DB::transaction(function () use ($product): void {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->path);
            }
            $product->delete();
        });

        return redirect()->route('merchant.products')
            ->with('success', 'تم حذف المنتج.');
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

    private function authorizeSellerProduct(Request $request, Product $product): void
    {
        if ($product->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
