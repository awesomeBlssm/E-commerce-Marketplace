<?php

namespace App\Http\Controllers\Shopper;

use App\Http\Controllers\Controller;
use App\Models\Shopper\Customer;
use App\Models\Shopper\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Customer $customer): JsonResponse
    {
        return response()->json($customer->wishlists()->withCount('items')->get());
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $wishlist = $customer->wishlists()->create($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_public' => ['sometimes', 'boolean'],
        ]));

        return response()->json($wishlist, 201);
    }

    public function show(Customer $customer, Wishlist $wishlist): JsonResponse
    {
        return response()->json($this->ownedWishlist($customer, $wishlist)->load('items.variant'));
    }

    public function update(Request $request, Customer $customer, Wishlist $wishlist): JsonResponse
    {
        $wishlist = $this->ownedWishlist($customer, $wishlist);
        $wishlist->update($request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'is_public' => ['sometimes', 'boolean'],
        ]));

        return response()->json($wishlist->fresh());
    }

    public function destroy(Customer $customer, Wishlist $wishlist): JsonResponse
    {
        $wishlist = $this->ownedWishlist($customer, $wishlist);
        $wishlist->delete();

        return response()->json(null, 204);
    }

    private function ownedWishlist(Customer $customer, Wishlist $wishlist): Wishlist
    {
        return $customer->wishlists()->findOrFail($wishlist->id);
    }
}
