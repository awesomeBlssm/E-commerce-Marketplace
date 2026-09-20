<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Provides CRUD operations for active fulfilment warehouses. */
class WarehouseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Warehouse::query()->withCount('inventoryItems')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $warehouse = Warehouse::create($this->validated($request));

        return response()->json($warehouse, 201);
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        return response()->json($warehouse->load('inventoryItems'));
    }

    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $warehouse->update($this->validated($request, true));

        return response()->json($warehouse->fresh());
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'code' => [$required, 'string', 'max:20', 'unique:warehouses,code'.($partial ? ','.$request->route('warehouse')->id : '')],
            'country_code' => [$required, 'string', 'size:2'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
