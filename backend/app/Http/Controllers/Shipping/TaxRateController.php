<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Models\Shipping\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages regional tax rates represented as integer basis points. */
class TaxRateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(TaxRate::query()->orderBy('country_code')->orderBy('region')->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $taxRate = TaxRate::create($this->validated($request));

        return response()->json($taxRate, 201);
    }

    public function show(TaxRate $taxRate): JsonResponse
    {
        return response()->json($taxRate);
    }

    public function update(Request $request, TaxRate $taxRate): JsonResponse
    {
        $taxRate->update($this->validated($request, true));

        return response()->json($taxRate->fresh());
    }

    public function destroy(TaxRate $taxRate): JsonResponse
    {
        $taxRate->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'country_code' => [$required, 'string', 'size:2'],
            'region' => ['sometimes', 'nullable', 'string', 'max:120'],
            'name' => [$required, 'string', 'max:120'],
            'rate_basis_points' => [$required, 'integer', 'min:0'],
        ]);
    }
}
