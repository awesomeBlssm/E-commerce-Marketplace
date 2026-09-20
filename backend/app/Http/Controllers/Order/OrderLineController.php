<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Reads and appends immutable product snapshots belonging to an order. */
class OrderLineController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        return response()->json($order->lines()->with('variant')->get());
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $line = $order->lines()->create($request->validate([
            'variant_id' => ['required', 'uuid', 'exists:product_variants,id'],
            'sku' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'variant_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price_cents' => ['required', 'integer', 'min:0'],
            'discount_cents' => ['required', 'integer', 'min:0'],
            'tax_cents' => ['required', 'integer', 'min:0'],
            'total_cents' => ['required', 'integer', 'min:0'],
        ]));

        return response()->json($line->load('variant'), 201);
    }

    public function show(Order $order, OrderLine $line): JsonResponse
    {
        return response()->json($this->ownedLine($order, $line)->load('variant'));
    }

    private function ownedLine(Order $order, OrderLine $line): OrderLine
    {
        return $order->lines()->findOrFail($line->id);
    }
}
