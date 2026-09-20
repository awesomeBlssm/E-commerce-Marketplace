<?php

use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Cart\CartItemController;
use Illuminate\Support\Facades\Route;

/** Cart routes keep item mutations nested so every item is scoped to its cart. */
Route::middleware('auth.api')->group(function () {
    Route::apiResource('carts', CartController::class)->only(['index', 'store']);
});

Route::middleware(['auth.api', 'resource.owner:cart'])->group(function () {
    Route::apiResource('carts', CartController::class)->only(['show', 'update', 'destroy']);
    Route::apiResource('carts.items', CartItemController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);
});
