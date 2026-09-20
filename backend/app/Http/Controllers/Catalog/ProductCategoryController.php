<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json($product->categories()->orderBy('position')->get());
    }

    public function store(Product $product, Category $category): JsonResponse
    {
        $product->categories()->syncWithoutDetaching([$category->id]);

        return response()->json($product->categories()->get(), 201);
    }

    public function destroy(Product $product, Category $category): JsonResponse
    {
        $product->categories()->detach($category->id);

        return response()->json(null, 204);
    }
}
