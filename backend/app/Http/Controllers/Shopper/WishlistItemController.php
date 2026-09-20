<?php

namespace App\Http\Controllers\Shopper;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistItemController extends Controller
{
    public function index(Wishlist $wishlist): JsonResponse
    {
        return response()->json($wishlist->items()->with('variant')->get());
    }

    public function store(Request $request, Wishlist $wishlist): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'uuid', 'exists:product_variants,id'],
        ]);

        $item = WishlistItem::query()->firstOrCreate([
            'wishlist_id' => $wishlist->id,
            'variant_id' => $data['variant_id'],
        ], [
            'added_at' => now(),
        ]);

        return response()->json($item->load('variant'), $item->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Wishlist $wishlist, ProductVariant $variant): JsonResponse
    {
        return response()->json($this->item($wishlist, $variant)->load('variant'));
    }

    public function destroy(Wishlist $wishlist, ProductVariant $variant): JsonResponse
    {
        $item = $this->item($wishlist, $variant);
        $item->delete();

        return response()->json(null, 204);
    }

    private function item(Wishlist $wishlist, ProductVariant $variant): WishlistItem
    {
        return $wishlist->items()->where('variant_id', $variant->id)->firstOrFail();
    }
}
