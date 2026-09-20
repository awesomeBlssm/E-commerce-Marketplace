<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductOptionController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json($product->options()->with('values')->orderBy('position')->get());
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $option = $product->options()->create($request->validate([
            'name' => ['required', 'string', 'max:60'],
            'position' => ['required', 'integer'],
        ]));

        return response()->json($option, 201);
    }

    public function show(Product $product, ProductOption $option): JsonResponse
    {
        return response()->json($this->ownedOption($product, $option)->load('values'));
    }

    public function update(Request $request, Product $product, ProductOption $option): JsonResponse
    {
        $option = $this->ownedOption($product, $option);
        $option->update($request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'position' => ['sometimes', 'integer'],
        ]));

        return response()->json($option->fresh());
    }

    public function destroy(Product $product, ProductOption $option): JsonResponse
    {
        $this->ownedOption($product, $option)->delete();

        return response()->json(null, 204);
    }

    private function ownedOption(Product $product, ProductOption $option): ProductOption
    {
        return $product->options()->findOrFail($option->id);
    }
}
