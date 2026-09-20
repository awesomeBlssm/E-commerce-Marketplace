<?php

namespace App\Http\Controllers\Money;

use App\Http\Controllers\Controller;
use App\Models\Money\PaymentMethod;
use App\Models\Shopper\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages customer payment references without exposing processor secrets. */
class PaymentMethodController extends Controller
{
    public function index(Customer $customer): JsonResponse
    {
        return response()->json($customer->paymentMethods()->get());
    }

    public function store(Request $request, Customer $customer): JsonResponse
    {
        $paymentMethod = $customer->paymentMethods()->create($this->validated($request));

        return response()->json($paymentMethod, 201);
    }

    public function show(Customer $customer, PaymentMethod $paymentMethod): JsonResponse
    {
        return response()->json($this->ownedMethod($customer, $paymentMethod));
    }

    public function update(Request $request, Customer $customer, PaymentMethod $paymentMethod): JsonResponse
    {
        $paymentMethod = $this->ownedMethod($customer, $paymentMethod);
        $paymentMethod->update($this->validated($request, true));

        return response()->json($paymentMethod->fresh());
    }

    public function destroy(Customer $customer, PaymentMethod $paymentMethod): JsonResponse
    {
        $this->ownedMethod($customer, $paymentMethod)->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'brand' => ['sometimes', 'nullable', 'string', 'max:40'],
            'last4' => ['sometimes', 'nullable', 'string', 'size:4'],
            'exp_month' => ['sometimes', 'nullable', 'integer', 'between:1,12'],
            'exp_year' => ['sometimes', 'nullable', 'integer', 'min:2000'],
            'processor_token' => [$required, 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }

    private function ownedMethod(Customer $customer, PaymentMethod $paymentMethod): PaymentMethod
    {
        return $customer->paymentMethods()->findOrFail($paymentMethod->id);
    }
}
