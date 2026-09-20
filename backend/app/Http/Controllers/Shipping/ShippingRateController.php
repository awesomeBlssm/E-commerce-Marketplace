<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Models\Shipping\ShippingRate;
use App\Models\Shipping\ShippingZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages shipping prices and their order-value or weight bounds within a zone. */
class ShippingRateController extends Controller
{
    public function index(ShippingZone $shippingZone): JsonResponse
    {
        return response()->json($shippingZone->rates()->get());
    }

    public function store(Request $request, ShippingZone $shippingZone): JsonResponse
    {
        $rate = $shippingZone->rates()->create($this->validated($request));

        return response()->json($rate, 201);
    }

    public function show(ShippingZone $shippingZone, ShippingRate $rate): JsonResponse
    {
        return response()->json($this->ownedRate($shippingZone, $rate));
    }

    public function update(Request $request, ShippingZone $shippingZone, ShippingRate $rate): JsonResponse
    {
        $rate = $this->ownedRate($shippingZone, $rate);
        $rate->update($this->validated($request, true));

        return response()->json($rate->fresh());
    }

    public function destroy(ShippingZone $shippingZone, ShippingRate $rate): JsonResponse
    {
        $this->ownedRate($shippingZone, $rate)->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'price_cents' => [$required, 'integer', 'min:0'],
            'currency' => [$required, 'string', 'size:3'],
            'min_order_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_order_cents' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'min_weight_grams' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_weight_grams' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);
    }

    private function ownedRate(ShippingZone $shippingZone, ShippingRate $rate): ShippingRate
    {
        return $shippingZone->rates()->findOrFail($rate->id);
    }
}
