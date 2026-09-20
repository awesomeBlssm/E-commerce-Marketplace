<?php

namespace App\Http\Controllers\Fulfillment;

use App\Http\Controllers\Controller;
use App\Models\Fulfillment\Shipment;
use App\Models\Order\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages parcel status and tracking while keeping each shipment tied to its order. */
class ShipmentController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        return response()->json($order->shipments()->with('warehouse')->withCount('lines')->get());
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $shipment = $order->shipments()->create($this->validated($request));

        return response()->json($shipment->load('warehouse'), 201);
    }

    public function show(Order $order, Shipment $shipment): JsonResponse
    {
        return response()->json($this->ownedShipment($order, $shipment)->load(['warehouse', 'lines.orderLine']));
    }

    public function update(Request $request, Order $order, Shipment $shipment): JsonResponse
    {
        $shipment = $this->ownedShipment($order, $shipment);
        $shipment->update($this->validated($request, true));

        return response()->json($shipment->fresh(['warehouse', 'lines.orderLine']));
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'warehouse_id' => [$required, 'uuid', 'exists:warehouses,id'],
            'status' => [$required, 'in:pending,in_transit,delivered,failed'],
            'carrier' => ['sometimes', 'nullable', 'string', 'max:80'],
            'tracking_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'shipped_at' => ['sometimes', 'nullable', 'date'],
            'delivered_at' => ['sometimes', 'nullable', 'date'],
        ]);
    }

    private function ownedShipment(Order $order, Shipment $shipment): Shipment
    {
        return $order->shipments()->findOrFail($shipment->id);
    }
}
