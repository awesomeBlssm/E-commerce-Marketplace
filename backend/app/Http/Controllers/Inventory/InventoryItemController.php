<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages the per-variant, per-warehouse stock balance. */
class InventoryItemController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(InventoryItem::query()->with(['variant', 'warehouse'])->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $item = InventoryItem::create($this->validated($request));

        return response()->json($item->load(['variant', 'warehouse']), 201);
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json($inventoryItem->load(['variant', 'warehouse', 'movements']));
    }

    public function update(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->update($this->validated($request, true));

        return response()->json($inventoryItem->fresh(['variant', 'warehouse']));
    }

    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $inventoryItem->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'variant_id' => [$required, 'uuid', 'exists:product_variants,id'],
            'warehouse_id' => [$required, 'uuid', 'exists:warehouses,id'],
            'on_hand' => [$required, 'integer', 'min:0'],
            'reserved' => [$required, 'integer', 'min:0'],
            'reorder_point' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);
    }
}
