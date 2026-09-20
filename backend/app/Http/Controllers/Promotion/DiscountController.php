<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use App\Models\Promotion\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages promotion definitions and their activation windows. */
class DiscountController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Discount::query()->withCount('redemptions')->latest('starts_at')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $discount = Discount::create($this->validated($request));

        return response()->json($discount, 201);
    }

    public function show(Discount $discount): JsonResponse
    {
        return response()->json($discount->loadCount('redemptions'));
    }

    public function update(Request $request, Discount $discount): JsonResponse
    {
        $discount->update($this->validated($request, true));

        return response()->json($discount->fresh());
    }

    public function destroy(Discount $discount): JsonResponse
    {
        $discount->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'code' => [$required, 'string', 'max:40', 'unique:discounts,code'.($partial ? ','.$request->route('discount')->id : '')],
            'type' => [$required, 'in:percentage,fixed_amount,free_shipping'],
            'value' => [$required, 'integer', 'min:0'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'minimum_spend_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'used_count' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
