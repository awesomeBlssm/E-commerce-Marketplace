<?php

use App\Http\Controllers\Catalog\ProductImageController;
use App\Http\Controllers\Catalog\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products.variants', ProductVariantController::class);
Route::apiResource('products.images', ProductImageController::class);
