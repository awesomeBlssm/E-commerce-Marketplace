<?php

use App\Http\Controllers\Catalog\BrandController;
use App\Http\Controllers\Catalog\CategoryController;
use App\Http\Controllers\Catalog\ProductCategoryController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Catalog\ProductOptionController;
use App\Http\Controllers\Catalog\ProductOptionValueController;
use Illuminate\Support\Facades\Route;

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
