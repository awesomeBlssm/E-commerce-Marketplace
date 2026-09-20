<?php

use App\Http\Controllers\ReverseFlow\RefundController;
use App\Http\Controllers\ReverseFlow\ReturnLineController;
use App\Http\Controllers\ReverseFlow\ReturnRequestController;
use Illuminate\Support\Facades\Route;

/** Reverse-flow routes manage RMAs, returned quantities, and refund records. */
Route::middleware(['auth.api', 'resource.owner:order'])->group(function () {
    Route::apiResource('orders.returns', ReturnRequestController::class)
        ->only(['index', 'store', 'show', 'update'])
        ->parameters(['returns' => 'returnRequest']);
});

Route::middleware(['auth.api', 'resource.owner:returnRequest'])->group(function () {
    Route::apiResource('returns.lines', ReturnLineController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['returns' => 'returnRequest']);
    Route::apiResource('returns.refunds', RefundController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['returns' => 'returnRequest']);
});
