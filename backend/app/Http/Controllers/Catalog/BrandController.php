<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Brand::query()->withCount('products')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $brand = Brand::create($request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brands,name'],
            'slug' => ['required', 'string', 'max:140', 'unique:brands,slug'],
            'description' => ['nullable', 'string'],
        ]));

        return response()->json($brand, 201);
    }

    public function show(Brand $brand): JsonResponse
    {
        return response()->json($brand->load('products'));
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $brand->update($request->validate([
            'name' => ['sometimes', 'string', 'max:120', 'unique:brands,name,'.$brand->id],
            'slug' => ['sometimes', 'string', 'max:140', 'unique:brands,slug,'.$brand->id],
            'description' => ['sometimes', 'nullable', 'string'],
        ]));

        return response()->json($brand->fresh());
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();

        return response()->json(null, 204);
    }
}
