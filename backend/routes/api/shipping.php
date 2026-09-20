<?php

use App\Http\Controllers\Shipping\ShippingRateController;
use App\Http\Controllers\Shipping\ShippingZoneController;
use App\Http\Controllers\Shipping\TaxRateController;
use Illuminate\Support\Facades\Route;

/** Shipping and tax routes manage destination rules, rates, and basis-point tax configuration. */
Route::apiResource('shipping-zones', ShippingZoneController::class)
    ->parameters(['shipping-zones' => 'shippingZone']);
Route::apiResource('shipping-zones.rates', ShippingRateController::class)
    ->parameters(['shipping-zones' => 'shippingZone']);
Route::apiResource('tax-rates', TaxRateController::class)
    ->parameters(['tax-rates' => 'taxRate']);
