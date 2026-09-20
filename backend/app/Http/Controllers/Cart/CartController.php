<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages customer and anonymous carts while preserving live variant pricing. */
class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Cart::query()->withCount('items')->latest('updated_at');

        if ($request->user()->type !== 'admin') {
            $customerId = $request->user()->customer?->id;
            $query->where('customer_id', $customerId);
        }

        return response()->json($query->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json(['message' => 'A customer profile is required to create a cart.'], 422);
        }

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
            'currency' => $request->validate([
                'currency' => ['required', 'string', 'size:3'],
            ])['currency'],
        ]);

        return response()->json($cart, 201);
    }

    public function show(Cart $cart): JsonResponse
    {
        return response()->json($cart->load('items.variant'));
    }

    public function update(Request $request, Cart $cart): JsonResponse
    {
        $cart->update($request->validate([
            'status' => ['sometimes', 'in:active,converted,abandoned'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ]));

        return response()->json($cart->fresh());
    }

    public function destroy(Cart $cart): JsonResponse
    {
        $cart->delete();

        return response()->json(null, 204);
    }
}
