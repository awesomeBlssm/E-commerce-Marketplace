<?php

use App\Http\Controllers\Promotion\DiscountController;
use App\Http\Controllers\Promotion\DiscountRedemptionController;
use App\Http\Controllers\Promotion\GiftCardController;
use App\Http\Controllers\Promotion\GiftCardTransactionController;
use Illuminate\Support\Facades\Route;

/** Promotion routes manage discounts, redemption snapshots, gift cards, and ledgers. */
Route::apiResource('discounts', DiscountController::class);
Route::apiResource('discounts.redemptions', DiscountRedemptionController::class)
    ->only(['index', 'store', 'show']);
Route::apiResource('gift-cards', GiftCardController::class)
    ->parameters(['gift-cards' => 'giftCard']);
Route::apiResource('gift-cards.transactions', GiftCardTransactionController::class)
    ->only(['index', 'store', 'show'])
    ->parameters(['gift-cards' => 'giftCard']);
