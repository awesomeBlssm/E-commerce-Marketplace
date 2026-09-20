<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Models\Shipping\ShippingZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages destination groups used to select shipping rates. */
class ShippingZoneController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ShippingZone::query()->withCount('rates')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $zone = ShippingZone::create($this->validated($request));

        return response()->json($zone, 201);
    }

    public function show(ShippingZone $shippingZone): JsonResponse
    {
        return response()->json($shippingZone->load('rates'));
    }

    public function update(Request $request, ShippingZone $shippingZone): JsonResponse
    {
        $shippingZone->update($this->validated($request, true));

        return response()->json($shippingZone->fresh());
    }

    public function destroy(ShippingZone $shippingZone): JsonResponse
    {
        $shippingZone->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:120'],
            'country_codes' => [$required, 'string'],
        ]);
    }
}
