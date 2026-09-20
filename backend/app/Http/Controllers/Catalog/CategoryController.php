<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Category::query()
            ->with(['parent', 'children'])
            ->withCount('products')
            ->orderBy('position')
            ->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $category = Category::create($this->validated($request));

        return response()->json($category, 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json($category->load(['parent', 'children', 'products']));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $category->update($this->validated($request, true));

        return response()->json($category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'parent_id' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'uuid', 'exists:categories,id'],
            'name' => [$required, 'string', 'max:120'],
            'slug' => [$required, 'string', 'max:140', 'unique:categories,slug'.($partial ? ','.$request->route('category')->id : '')],
            'position' => [$required, 'integer'],
        ]);
    }
}
