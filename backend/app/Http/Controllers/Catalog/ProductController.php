<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with([
                'brand',
                'categories',
                'activeVariants:id,product_id,price_cents,compare_at_cents,currency,is_active',
                'firstImage:id,product_id,url,alt_text,position',
            ])
            ->withCount(['variants', 'reviews'])
            ->where('status', 'active')
            ->whereNotNull('published_at');

        if ($request->filled('category')) {
            $category = $request->query('category');
            $query->whereHas('categories', function ($q) use ($category) {
                $q->where('name', $category)->orWhere('slug', $category);
            });
        }

        return response()->json($query->latest('created_at')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($request->user()->type === 'seller') {
            $data['seller_id'] = $request->user()->id;
        }

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load([
            'brand',
            'categories',
            'options.values',
            'variants.optionValues.option',
            'images',
        ]));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request, true);

        if ($request->user()->type === 'seller') {
            $data['seller_id'] = $request->user()->id;
        }

        $product->update($data);

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
            'seller_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'title' => [$required, 'string', 'max:255'],
            'slug' => [$required, 'string', 'max:280', 'unique:products,slug'.($partial ? ','.$request->route('product')->id : '')],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => [$required, 'in:draft,active,archived'],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ]);
    }
}
