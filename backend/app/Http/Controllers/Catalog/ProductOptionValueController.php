<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductOptionValueController extends Controller
{
    public function index(Product $product, ProductOption $option): JsonResponse
    {
        return response()->json($this->ownedOption($product, $option)->values()->orderBy('position')->get());
    }

    public function store(Request $request, Product $product, ProductOption $option): JsonResponse
    {
        $value = $this->ownedOption($product, $option)->values()->create($request->validate([
            'value' => ['required', 'string', 'max:80'],
            'position' => ['required', 'integer'],
        ]));

        return response()->json($value, 201);
    }

    public function show(Product $product, ProductOption $option, ProductOptionValue $value): JsonResponse
    {
        return response()->json($this->ownedValue($product, $option, $value));
    }

    public function update(Request $request, Product $product, ProductOption $option, ProductOptionValue $value): JsonResponse
    {
        $value = $this->ownedValue($product, $option, $value);
        $value->update($request->validate([
            'value' => ['sometimes', 'string', 'max:80'],
            'position' => ['sometimes', 'integer'],
        ]));

        return response()->json($value->fresh());
    }

    public function destroy(Product $product, ProductOption $option, ProductOptionValue $value): JsonResponse
    {
        $this->ownedValue($product, $option, $value)->delete();

        return response()->json(null, 204);
    }

    private function ownedOption(Product $product, ProductOption $option): ProductOption
    {
        return $product->options()->findOrFail($option->id);
    }

    private function ownedValue(Product $product, ProductOption $option, ProductOptionValue $value): ProductOptionValue
    {
        return $this->ownedOption($product, $option)->values()->findOrFail($value->id);
    }
}
