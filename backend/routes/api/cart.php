<?php

use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Cart\CartItemController;
use Illuminate\Support\Facades\Route;

/** Cart routes keep item mutations nested so every item is scoped to its cart. */
Route::apiResource('carts', CartController::class);
Route::apiResource('carts.items', CartItemController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);
