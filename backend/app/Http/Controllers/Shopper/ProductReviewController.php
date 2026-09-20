<?php

namespace App\Http\Controllers\Shopper;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Shopper\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json($product->reviews()
            ->where('status', 'published')
            ->with('customer:id,full_name')
            ->latest('created_at')
            ->paginate());
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:160'],
            'body' => ['nullable', 'string'],
        ]);

        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json(['message' => 'A customer profile is required to submit a review.'], 422);
        }

        $review = $product->reviews()->create([
            ...$data,
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        return response()->json($review, 201);
    }

    public function show(ProductReview $review): JsonResponse
    {
        return response()->json($review->load(['product', 'customer']));
    }

    public function update(Request $request, ProductReview $review): JsonResponse
    {
        $review->update($request->validate([
            'rating' => ['sometimes', 'integer', 'between:1,5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'body' => ['sometimes', 'nullable', 'string'],
        ]));

        return response()->json($review->fresh());
    }

    public function destroy(ProductReview $review): JsonResponse
    {
        $review->delete();

        return response()->json(null, 204);
    }
}
