<?php

use App\Http\Controllers\Money\PaymentController;
use App\Http\Controllers\Money\PaymentMethodController;
use Illuminate\Support\Facades\Route;

/** Money routes manage processor-backed payment references and order payment attempts. */
Route::apiResource('customers.payment-methods', PaymentMethodController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy'])
    ->parameters(['payment-methods' => 'paymentMethod']);
Route::apiResource('orders.payments', PaymentController::class)
    ->only(['index', 'store', 'show', 'update']);
