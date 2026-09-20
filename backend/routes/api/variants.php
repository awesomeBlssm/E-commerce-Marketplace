<?php

use App\Http\Controllers\Catalog\ProductImageController;
use App\Http\Controllers\Catalog\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products.variants', ProductVariantController::class)->only(['index', 'show']);
Route::apiResource('products.images', ProductImageController::class)->only(['index', 'show']);

Route::middleware(['auth.api', 'user.type:seller,admin'])->group(function () {
	Route::apiResource('products.variants', ProductVariantController::class)->only(['store']);
	Route::apiResource('products.images', ProductImageController::class)->only(['store']);
});

Route::middleware(['auth.api', 'user.type:seller,admin', 'resource.owner:product'])->group(function () {
	Route::apiResource('products.variants', ProductVariantController::class)->only(['update', 'destroy']);
	Route::apiResource('products.images', ProductImageController::class)->only(['update', 'destroy']);
});
