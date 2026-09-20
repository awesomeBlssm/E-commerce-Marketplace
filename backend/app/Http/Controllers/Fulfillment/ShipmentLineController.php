<?php

namespace App\Http\Controllers\Fulfillment;

use App\Http\Controllers\Controller;
use App\Models\Fulfillment\Shipment;
use App\Models\Fulfillment\ShipmentLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Adds and reads parcel quantities assigned to immutable order lines. */
class ShipmentLineController extends Controller
{
    public function index(Shipment $shipment): JsonResponse
    {
        return response()->json($shipment->lines()->with('orderLine')->get());
    }

    public function store(Request $request, Shipment $shipment): JsonResponse
    {
        $data = $request->validate([
            'order_line_id' => ['required', 'uuid', 'exists:order_lines,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $shipment->order->lines()->findOrFail($data['order_line_id']);
        $line = $shipment->lines()->create($data);

        return response()->json($line->load('orderLine'), 201);
    }

    public function show(Shipment $shipment, ShipmentLine $line): JsonResponse
    {
        return response()->json($shipment->lines()->findOrFail($line->id)->load('orderLine'));
    }
}
