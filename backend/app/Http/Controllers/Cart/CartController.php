<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages customer and anonymous carts while preserving live variant pricing. */
class CartController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Cart::query()->withCount('items')->latest('updated_at')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $cart = Cart::create($this->validated($request));

        return response()->json($cart, 201);
    }

    public function show(Cart $cart): JsonResponse
    {
        return response()->json($cart->load('items.variant'));
    }

    public function update(Request $request, Cart $cart): JsonResponse
    {
        $cart->update($this->validated($request, true));

        return response()->json($cart->fresh());
    }

    public function destroy(Cart $cart): JsonResponse
    {
        $cart->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'customer_id' => ['sometimes', 'nullable', 'uuid', 'exists:customers,id'],
            'session_token' => ['sometimes', 'nullable', 'string', 'max:64', 'unique:carts,session_token'.($partial ? ','.$request->route('cart')->id : '')],
            'status' => [$required, 'in:active,converted,abandoned'],
            'currency' => [$required, 'string', 'size:3'],
        ]);
    }
}
