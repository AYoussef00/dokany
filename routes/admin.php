<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\SubscriptionRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('requests', [SubscriptionRequestController::class, 'index'])->name('admin.requests');
    Route::post('requests/{user}/approve', [SubscriptionRequestController::class, 'approve'])->name('admin.requests.approve');
    Route::post('requests/{user}/reject', [SubscriptionRequestController::class, 'reject'])->name('admin.requests.reject');

    Route::get('sellers', [SellerController::class, 'index'])->name('admin.sellers');
    Route::post('sellers', [SellerController::class, 'store'])->name('admin.sellers.store');
    Route::delete('sellers/{user}', [SellerController::class, 'destroy'])->name('admin.sellers.destroy');
    Route::inertia('settings', 'admin/Settings')->name('admin.settings');
});
