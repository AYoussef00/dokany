<?php

declare(strict_types=1);

use App\Http\Controllers\Merchant\CategoriesController;
use App\Http\Controllers\Merchant\MerchantInvoiceController;
use App\Http\Controllers\Merchant\MerchantOrderController;
use App\Http\Controllers\Merchant\MerchantPaymentController;
use App\Http\Controllers\Merchant\MerchantPaymentLinkController;
use App\Http\Controllers\Merchant\ProductController;
use App\Http\Controllers\Merchant\StoreSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Merchant (seller) panel
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'merchant.subscription_access', 'seller'])->group(function (): void {
    Route::get('store-settings', [StoreSettingsController::class, 'edit'])->name('merchant.store-settings.edit');
    Route::patch('store-settings', [StoreSettingsController::class, 'update'])->name('merchant.store-settings.update');
    Route::get('categories', [CategoriesController::class, 'index'])->name('merchant.categories');
    Route::post('categories', [CategoriesController::class, 'store'])->name('merchant.categories.store');
    Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->name('merchant.categories.destroy');
    Route::get('products', [ProductController::class, 'index'])->name('merchant.products');
    Route::post('products', [ProductController::class, 'store'])->name('merchant.products.store');
    Route::patch('products/{product}', [ProductController::class, 'update'])->name('merchant.products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('merchant.products.destroy');
    Route::get('orders', [MerchantOrderController::class, 'index'])->name('merchant.orders');
    Route::post('orders/{order}/accept', [MerchantOrderController::class, 'accept'])->name('merchant.orders.accept');
    Route::post('orders/{order}/reject', [MerchantOrderController::class, 'reject'])->name('merchant.orders.reject');
    Route::delete('orders/{order}', [MerchantOrderController::class, 'destroy'])->name('merchant.orders.destroy');
    Route::get('payment-links', [MerchantPaymentLinkController::class, 'index'])->name('merchant.payment-links');
    Route::post('payment-links', [MerchantPaymentLinkController::class, 'store'])->name('merchant.payment-links.store');
    Route::get('invoices', [MerchantInvoiceController::class, 'index'])->name('merchant.invoices');
    Route::get('payments', [MerchantPaymentController::class, 'index'])->name('merchant.payments');
});
