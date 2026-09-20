<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\Promotion\GiftCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages gift-card issuance and lifecycle while balances change through transactions. */
class GiftCardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(GiftCard::query()->with('issuedToCustomer')->withCount('transactions')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:gift_cards,code'],
            'initial_balance_cents' => ['required', 'integer', 'min:0'],
            'balance_cents' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', 'in:active,redeemed,expired,cancelled'],
            'issued_to_customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ]);
        $data['balance_cents'] ??= $data['initial_balance_cents'];

        $giftCard = GiftCard::create($data);

        return response()->json($giftCard->load('issuedToCustomer'), 201);
    }

    public function show(GiftCard $giftCard): JsonResponse
    {
        return response()->json($giftCard->load(['issuedToCustomer', 'transactions.order']));
    }

    public function update(Request $request, GiftCard $giftCard): JsonResponse
    {
        $giftCard->update($request->validate([
            'status' => ['sometimes', 'in:active,redeemed,expired,cancelled'],
            'issued_to_customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ]));

        return response()->json($giftCard->fresh('issuedToCustomer'));
    }

    public function destroy(GiftCard $giftCard): JsonResponse
    {
        $giftCard->delete();

        return response()->json(null, 204);
    }
}
