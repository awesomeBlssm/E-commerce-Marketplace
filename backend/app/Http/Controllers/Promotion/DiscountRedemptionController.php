<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Promotion\Discount;
use App\Models\Promotion\DiscountRedemption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Records discount usage as an immutable order and customer snapshot. */
class DiscountRedemptionController extends Controller
{
    public function index(Discount $discount): JsonResponse
    {
        return response()->json($discount->redemptions()->with(['order', 'customer'])->latest('redeemed_at')->paginate());
    }

    public function store(Request $request, Discount $discount): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'amount_cents' => ['required', 'integer', 'min:0'],
            'redeemed_at' => ['sometimes', 'date'],
        ]);

        $order = Order::query()->whereKey($data['order_id'])->where('customer_id', $data['customer_id'])->firstOrFail();
        $data['customer_id'] = $order->customer_id;
        $redemption = $discount->redemptions()->create($data);

        return response()->json($redemption->load(['order', 'customer']), 201);
    }

    public function show(Discount $discount, DiscountRedemption $redemption): JsonResponse
    {
        return response()->json($discount->redemptions()->findOrFail($redemption->id)->load(['order', 'customer']));
    }
}
