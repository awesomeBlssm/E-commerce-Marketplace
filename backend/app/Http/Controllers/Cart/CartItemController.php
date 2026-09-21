<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Adds, updates, and removes live-priced variants within a cart. */
class CartItemController extends Controller
{
    public function index(Cart $cart): JsonResponse
    {
        return response()->json($cart->items()->with(
            'variant.images',
            'variant.product.images',
            'variant.optionValues.option'
        )->get());
    }

    public function store(Request $request, Cart $cart): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'uuid', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = CartItem::query()->firstOrCreate([
            'cart_id' => $cart->id,
            'variant_id' => $data['variant_id'],
        ], [
            'quantity' => $data['quantity'],
            'added_at' => now(),
        ]);

        if (! $item->wasRecentlyCreated) {
            $item->increment('quantity', $data['quantity']);
        }

        return response()->json($item->load(
            'variant.images',
            'variant.product.images',
            'variant.optionValues.option'
        ), $item->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Cart $cart, CartItem $item): JsonResponse
    {
        return response()->json($this->ownedItem($cart, $item)->load(
            'variant.images',
            'variant.product.images',
            'variant.optionValues.option'
        ));
    }

    public function update(Request $request, Cart $cart, CartItem $item): JsonResponse
    {
        $item = $this->ownedItem($cart, $item);
        $item->update($request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]));

        return response()->json($item->fresh([
            'variant.images',
            'variant.product.images',
            'variant.optionValues.option'
        ]));
    }

    public function destroy(Cart $cart, CartItem $item): JsonResponse
    {
        $this->ownedItem($cart, $item)->delete();

        return response()->json(null, 204);
    }

    private function ownedItem(Cart $cart, CartItem $item): CartItem
    {
        return $cart->items()->findOrFail($item->id);
    }
}
