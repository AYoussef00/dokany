<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Onboarding\SubscriptionPaymentController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\OrderPaymentController;
use App\Http\Controllers\Storefront\StorefrontPaymentLinkController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

$storefrontPrefix = trim(config('dokany.storefront_path_prefix', 'shop'), '/');
$slugPattern = '[a-z0-9]+(?:-[a-z0-9]+)*';
$uuidPattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
Route::get($storefrontPrefix.'/{slug}/checkout', [CheckoutController::class, 'create'])
    ->name('storefront.checkout')
    ->where('slug', $slugPattern);
Route::post($storefrontPrefix.'/{slug}/checkout', [CheckoutController::class, 'store'])
    ->name('storefront.checkout.store')
    ->where('slug', $slugPattern);
Route::get($storefrontPrefix.'/{slug}/pay-link/{linkUuid}', [StorefrontPaymentLinkController::class, 'show'])
    ->name('storefront.pay-link.show')
    ->where('slug', $slugPattern)
    ->where('linkUuid', $uuidPattern);
Route::post($storefrontPrefix.'/{slug}/pay-link/{linkUuid}', [StorefrontPaymentLinkController::class, 'store'])
    ->name('storefront.pay-link.store')
    ->where('slug', $slugPattern)
    ->where('linkUuid', $uuidPattern);
Route::get($storefrontPrefix.'/{slug}/order/{order}/pay', [OrderPaymentController::class, 'show'])
    ->name('storefront.order.pay')
    ->where('slug', $slugPattern);
Route::post($storefrontPrefix.'/{slug}/order/{order}/pay', [OrderPaymentController::class, 'update'])
    ->name('storefront.order.pay.store')
    ->where('slug', $slugPattern);
Route::get($storefrontPrefix.'/{slug}', [StorefrontController::class, 'show'])
    ->name('storefront.show')
    ->where('slug', $slugPattern);

Route::middleware(['auth', 'verified', 'merchant.subscription_access'])->group(function () {
    Route::get('onboarding/subscription-payment', [SubscriptionPaymentController::class, 'show'])
        ->name('onboarding.subscription-payment');
    Route::post('onboarding/subscription-payment', [SubscriptionPaymentController::class, 'store'])
        ->name('onboarding.subscription-payment.store');

    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

Route::prefix('admin')
    ->middleware('web')
    ->group(base_path('routes/admin.php'));

Route::prefix('merchant')
    ->middleware('web')
    ->group(base_path('routes/merchant.php'));

require __DIR__.'/settings.php';
