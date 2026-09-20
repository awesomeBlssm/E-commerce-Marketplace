<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Promotion\GiftCard;
use App\Models\Promotion\GiftCardTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Appends signed gift-card balance movements without deleting ledger history. */
class GiftCardTransactionController extends Controller
{
    public function index(GiftCard $giftCard): JsonResponse
    {
        return response()->json($giftCard->transactions()->with('order')->latest('occurred_at')->paginate());
    }

    public function store(Request $request, GiftCard $giftCard): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['sometimes', 'nullable', 'uuid', 'exists:orders,id'],
            'amount_cents' => ['required', 'integer', 'not_in:0'],
            'occurred_at' => ['sometimes', 'date'],
        ]);

        if (isset($data['order_id'])) {
            Order::query()->findOrFail($data['order_id']);
        }

        $transaction = DB::transaction(function () use ($giftCard, $data): GiftCardTransaction {
            $transaction = $giftCard->transactions()->create($data);
            $giftCard->increment('balance_cents', $data['amount_cents']);

            return $transaction;
        });

        return response()->json($transaction->load('order'), 201);
    }

    public function show(GiftCard $giftCard, GiftCardTransaction $transaction): JsonResponse
    {
        return response()->json($giftCard->transactions()->findOrFail($transaction->id)->load('order'));
    }
}
