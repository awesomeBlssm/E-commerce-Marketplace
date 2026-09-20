<?php

use App\Http\Controllers\Shopper\CustomerAddressController;
use App\Http\Controllers\Shopper\CustomerController;
use App\Http\Controllers\Shopper\ProductReviewController;
use App\Http\Controllers\Shopper\WishlistController;
use App\Http\Controllers\Shopper\WishlistItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth.api', 'user.type:admin'])->group(function () {
    Route::apiResource('customers', CustomerController::class)->only(['index']);
});

Route::middleware(['auth.api', 'resource.owner:customer'])->group(function () {
    Route::apiResource('customers', CustomerController::class)
        ->only(['show', 'update', 'destroy']);
    Route::apiResource('customers.addresses', CustomerAddressController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::apiResource('customers.wishlists', WishlistController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
});

Route::middleware(['auth.api', 'resource.owner:wishlist'])->group(function () {
    Route::get('wishlists/{wishlist}/items', [WishlistItemController::class, 'index'])
        ->name('wishlists.items.index');
    Route::post('wishlists/{wishlist}/items', [WishlistItemController::class, 'store'])
        ->name('wishlists.items.store');
    Route::get('wishlists/{wishlist}/items/{variant}', [WishlistItemController::class, 'show'])
        ->name('wishlists.items.show');
    Route::delete('wishlists/{wishlist}/items/{variant}', [WishlistItemController::class, 'destroy'])
        ->name('wishlists.items.destroy');
});

Route::apiResource('products.reviews', ProductReviewController::class)
    ->only(['index'])
    ->shallow();
Route::middleware('auth.api')->group(function () {
    Route::apiResource('products.reviews', ProductReviewController::class)
        ->only(['store'])
        ->shallow();
    Route::apiResource('reviews', ProductReviewController::class)
        ->only(['show', 'update', 'destroy']);
});
