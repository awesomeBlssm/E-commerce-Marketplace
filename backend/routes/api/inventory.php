<?php

use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\WarehouseController;
use Illuminate\Support\Facades\Route;

/** Inventory routes expose warehouses, stock balances, and the append-only ledger. */
Route::apiResource('warehouses', WarehouseController::class);
Route::apiResource('inventory-items', InventoryItemController::class);
Route::apiResource('inventory-items.movements', InventoryMovementController::class)
    ->only(['index', 'store', 'show']);
