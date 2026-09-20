<?php

use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductCategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\ProductOptionController;
use App\Http\Controllers\Catalog\ProductOptionValueController;
use Illuminate\Support\Facades\Route;

Route::apiResource('brands', BrandController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('products', ProductController::class)->only(['index', 'show']);

Route::middleware(['auth.api', 'user.type:admin'])->group(function () {
    Route::apiResource('brands', BrandController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);
});

Route::middleware(['auth.api', 'user.type:seller,admin'])->group(function () {
    Route::apiResource('products', ProductController::class)->only(['store']);
});

Route::middleware(['auth.api', 'user.type:seller,admin', 'resource.owner:product'])->group(function () {
    Route::apiResource('products', ProductController::class)->only(['update', 'destroy']);
});

Route::get('products/{product}/categories', [ProductCategoryController::class, 'index'])
    ->name('products.categories.index');
Route::middleware(['auth.api', 'user.type:seller,admin', 'resource.owner:product'])->group(function () {
    Route::post('products/{product}/categories/{category}', [ProductCategoryController::class, 'store'])
        ->name('products.categories.store');
    Route::delete('products/{product}/categories/{category}', [ProductCategoryController::class, 'destroy'])
        ->name('products.categories.destroy');
});

Route::apiResource('products.options', ProductOptionController::class)->only(['index', 'show']);
Route::apiResource('products.options.values', ProductOptionValueController::class)->only(['index', 'show']);

Route::middleware(['auth.api', 'user.type:seller,admin', 'resource.owner:product'])->group(function () {
    Route::apiResource('products.options', ProductOptionController::class)
        ->only(['store', 'update', 'destroy']);
    Route::apiResource('products.options.values', ProductOptionValueController::class)
        ->only(['store', 'update', 'destroy']);
});
