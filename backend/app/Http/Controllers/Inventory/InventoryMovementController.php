<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Exposes the append-only stock movement ledger for an inventory item. */
class InventoryMovementController extends Controller
{
    public function index(InventoryItem $inventoryItem): JsonResponse
    {
        return response()->json($inventoryItem->movements()->latest('occurred_at')->paginate());
    }

    public function store(Request $request, InventoryItem $inventoryItem): JsonResponse
    {
        $movement = $inventoryItem->movements()->create($request->validate([
            'type' => ['required', 'in:receipt,sale,return,adjustment,transfer'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reference_type' => ['sometimes', 'nullable', 'string', 'max:40'],
            'reference_id' => ['sometimes', 'nullable', 'uuid'],
            'occurred_at' => ['sometimes', 'date'],
        ]));

        return response()->json($movement, 201);
    }

    public function show(InventoryItem $inventoryItem, InventoryMovement $movement): JsonResponse
    {
        return response()->json($inventoryItem->movements()->findOrFail($movement->id));
    }
}
