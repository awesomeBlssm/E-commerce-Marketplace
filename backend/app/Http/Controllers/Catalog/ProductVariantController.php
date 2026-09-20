<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json($product->variants()->with(['optionValues', 'images'])->get());
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate($this->rules());
        $optionValueIds = $data['option_value_ids'] ?? [];
        unset($data['option_value_ids']);

        $variant = $product->variants()->create($data);
        $variant->optionValues()->sync($this->validOptionValueIds($product, $optionValueIds));

        return response()->json($variant->load('optionValues'), 201);
    }

    public function show(Product $product, ProductVariant $variant): JsonResponse
    {
        return response()->json($this->ownedVariant($product, $variant)->load(['optionValues', 'images']));
    }

    public function update(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        $variant = $this->ownedVariant($product, $variant);
        $data = $request->validate($this->rules(true));
        $optionValueIds = $data['option_value_ids'] ?? null;
        unset($data['option_value_ids']);

        $variant->update($data);
        if ($optionValueIds !== null) {
            $variant->optionValues()->sync($this->validOptionValueIds($product, $optionValueIds));
        }

        return response()->json($variant->fresh()->load('optionValues'));
    }

    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->ownedVariant($product, $variant)->delete();

        return response()->json(null, 204);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'sku' => [$required, 'string', 'max:64', 'unique:product_variants,sku'.($partial ? ','.request()->route('variant')->id : '')],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:64'],
            'price_cents' => [$required, 'integer'],
            'compare_at_cents' => ['sometimes', 'nullable', 'integer'],
            'currency' => [$required, 'string', 'size:3'],
            'weight_grams' => ['sometimes', 'nullable', 'integer'],
            'requires_shipping' => [$required, 'boolean'],
            'is_active' => [$required, 'boolean'],
            'option_value_ids' => ['sometimes', 'array'],
            'option_value_ids.*' => ['uuid', 'distinct', 'exists:product_option_values,id'],
        ];
    }

    private function ownedVariant(Product $product, ProductVariant $variant): ProductVariant
    {
        return $product->variants()->findOrFail($variant->id);
    }

    private function validOptionValueIds(Product $product, array $ids): array
    {
        return $product->options()->whereHas('values', function ($query) use ($ids): void {
            $query->whereIn('product_option_values.id', $ids);
        })->with('values')->get()->flatMap(fn ($option) => $option->values->pluck('id'))
            ->intersect($ids)->values()->all();
    }
}
