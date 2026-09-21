<?php

namespace App\Http\Controllers\Shopper;

use App\Http\Controllers\Controller;
use App\Models\Shopper\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Customer::query()->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $customer = Customer::create($request->validate([
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
        $userId = $customer->user_id;

        $validated = $request->validate([
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                $userId ? Rule::unique('users', 'email')->ignore($userId) : Rule::unique('users', 'email'),
            ],
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
