<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json($product->images()->orderBy('position')->get());
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'url' => ['required', 'url', 'max:500'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position' => ['required', 'integer'],
        ]);

        $this->validateVariant($product, $data['variant_id'] ?? null);
        $image = $product->images()->create($data);

        return response()->json($image, 201);
    }

    public function show(Product $product, ProductImage $image): JsonResponse
    {
        return response()->json($this->ownedImage($product, $image));
    }

    public function update(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $image = $this->ownedImage($product, $image);
        $data = $request->validate([
            'variant_id' => ['sometimes', 'nullable', 'uuid', 'exists:product_variants,id'],
            'url' => ['sometimes', 'url', 'max:500'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position' => ['sometimes', 'integer'],
        ]);

        $this->validateVariant($product, $data['variant_id'] ?? null);
        $image->update($data);

        return response()->json($image->fresh());
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        $this->ownedImage($product, $image)->delete();

        return response()->json(null, 204);
    }

    private function ownedImage(Product $product, ProductImage $image): ProductImage
    {
        return $product->images()->findOrFail($image->id);
    }

    private function validateVariant(Product $product, ?string $variantId): void
    {
        if ($variantId !== null) {
            $product->variants()->findOrFail($variantId);
        }
    }
}
