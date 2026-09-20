<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Product::query()
            ->with(['brand', 'categories'])
            ->withCount(['variants', 'reviews'])
            ->latest('created_at')
            ->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validated($request));

        return response()->json($product, 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load(['brand', 'categories', 'options.values', 'variants', 'images']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->validated($request, true));

        return response()->json($product->fresh());
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'brand_id' => ['sometimes', 'nullable', 'uuid', 'exists:brands,id'],
            'title' => [$required, 'string', 'max:255'],
            'slug' => [$required, 'string', 'max:280', 'unique:products,slug'.($partial ? ','.$request->route('product')->id : '')],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => [$required, 'in:draft,active,archived'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ]);
    }
}
