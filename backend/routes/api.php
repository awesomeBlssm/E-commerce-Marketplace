<?php

use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductCategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\ProductImageController;
use App\Http\Controllers\Catalog\ProductOptionController;
use App\Http\Controllers\Catalog\ProductOptionValueController;
use App\Http\Controllers\Catalog\ProductVariantController;
use App\Http\Controllers\Shopper\CustomerAddressController;
use App\Http\Controllers\Shopper\CustomerController;
use App\Http\Controllers\Shopper\ProductReviewController;
use App\Http\Controllers\Shopper\WishlistController;
use App\Http\Controllers\Shopper\WishlistItemController;
use Illuminate\Support\Facades\Route;

Route::apiResource('customers', CustomerController::class);

Route::apiResource('customers.addresses', CustomerAddressController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

Route::apiResource('customers.wishlists', WishlistController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

Route::get('wishlists/{wishlist}/items', [WishlistItemController::class, 'index'])
    ->name('wishlists.items.index');
Route::post('wishlists/{wishlist}/items', [WishlistItemController::class, 'store'])
    ->name('wishlists.items.store');
Route::get('wishlists/{wishlist}/items/{variant}', [WishlistItemController::class, 'show'])
    ->name('wishlists.items.show');
Route::delete('wishlists/{wishlist}/items/{variant}', [WishlistItemController::class, 'destroy'])
    ->name('wishlists.items.destroy');

Route::apiResource('products.reviews', ProductReviewController::class)
    ->only(['index', 'store'])
    ->shallow();

Route::apiResource('reviews', ProductReviewController::class)
    ->only(['show', 'update', 'destroy']);

Route::apiResource('brands', BrandController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);

Route::get('products/{product}/categories', [ProductCategoryController::class, 'index'])
    ->name('products.categories.index');
Route::post('products/{product}/categories/{category}', [ProductCategoryController::class, 'store'])
    ->name('products.categories.store');
Route::delete('products/{product}/categories/{category}', [ProductCategoryController::class, 'destroy'])
    ->name('products.categories.destroy');

Route::apiResource('products.options', ProductOptionController::class);
Route::apiResource('products.options.values', ProductOptionValueController::class);
Route::apiResource('products.variants', ProductVariantController::class);
Route::apiResource('products.images', ProductImageController::class);
