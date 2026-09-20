<?php

namespace App\Http\Controllers\Shopper;

use App\Http\Controllers\Controller;
use App\Models\Shopper\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Customer::query()->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $customer = Customer::create($request->validate([
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'email' => ['required', 'email', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'accepts_marketing' => ['sometimes', 'boolean'],
        ]));

        return response()->json($customer, 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json($customer->load(['addresses', 'wishlists']));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validate([
            'user_id' => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
            'email' => ['sometimes', 'email', 'max:255'],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'accepts_marketing' => ['sometimes', 'boolean'],
        ]));

        return response()->json($customer->fresh());
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
