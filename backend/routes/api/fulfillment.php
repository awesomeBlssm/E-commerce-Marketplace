<?php

use App\Http\Controllers\Fulfillment\ShipmentController;
use App\Http\Controllers\Fulfillment\ShipmentLineController;
use Illuminate\Support\Facades\Route;

/** Fulfilment routes manage parcel lifecycle and quantities assigned to shipments. */
Route::apiResource('orders.shipments', ShipmentController::class)
    ->only(['index', 'store', 'show', 'update']);
Route::apiResource('shipments.lines', ShipmentLineController::class)
    ->only(['index', 'store', 'show']);
