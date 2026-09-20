<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Reads and appends frozen billing or shipping addresses belonging to an order. */
class OrderAddressController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        return response()->json($order->addresses()->get());
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $address = $order->addresses()->create($request->validate([
            'type' => ['required', 'in:billing,shipping'],
            'full_name' => ['required', 'string', 'max:120'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'region' => ['sometimes', 'nullable', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]));

        return response()->json($address, 201);
    }

    public function show(Order $order, OrderAddress $address): JsonResponse
    {
        return response()->json($this->ownedAddress($order, $address));
    }

    private function ownedAddress(Order $order, OrderAddress $address): OrderAddress
    {
        return $order->addresses()->findOrFail($address->id);
    }
}
