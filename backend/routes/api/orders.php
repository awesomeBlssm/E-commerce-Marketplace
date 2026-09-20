<?php

use App\Http\Controllers\Order\OrderAddressController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\OrderLineController;
use Illuminate\Support\Facades\Route;

/** Order routes expose immutable checkout snapshots and their nested records. */
Route::middleware(['auth.api', 'resource.owner:order'])->group(function () {
	Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
	Route::apiResource('orders.lines', OrderLineController::class)->only(['index', 'store', 'show']);
	Route::apiResource('orders.addresses', OrderAddressController::class)->only(['index', 'store', 'show']);
});
