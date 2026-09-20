<?php

namespace App\Http\Controllers\Shopper;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerAddressController extends Controller
{
    public function index(Customer $customer): JsonResponse
    {
        return response()->json($customer->addresses()->get());
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $address = $customer->addresses()->create($this->validated($request));

        return response()->json($address, 201);
    }

    public function show(Customer $customer, CustomerAddress $address): JsonResponse
    {
        return response()->json($this->ownedAddress($customer, $address));
    }

    public function update(Request $request, Customer $customer, CustomerAddress $address): JsonResponse
    {
        $address = $this->ownedAddress($customer, $address);
        $address->update($this->validated($request, true));

        return response()->json($address->fresh());
    }

    public function destroy(Customer $customer, CustomerAddress $address): JsonResponse
    {
        $address = $this->ownedAddress($customer, $address);
        $address->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'type' => [$required, 'in:billing,shipping'],
            'line1' => [$required, 'string', 'max:255'],
            'line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => [$required, 'string', 'max:120'],
            'region' => ['sometimes', 'nullable', 'string', 'max:120'],
            'postal_code' => [$required, 'string', 'max:20'],
            'country_code' => [$required, 'string', 'size:2'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }

    private function ownedAddress(Customer $customer, CustomerAddress $address): CustomerAddress
    {
        return $customer->addresses()->findOrFail($address->id);
    }
}
