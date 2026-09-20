<?php

namespace App\Http\Controllers\Money;

use App\Http\Controllers\Controller;
use App\Models\Money\Payment;
use App\Models\Order\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Records payment attempts and status transitions for an order in minor units. */
class PaymentController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        return response()->json($order->payments()->with('paymentMethod')->latest()->get());
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $payment = $order->payments()->create($this->validated($request));

        return response()->json($payment->load('paymentMethod'), 201);
    }

    public function show(Order $order, Payment $payment): JsonResponse
    {
        return response()->json($this->ownedPayment($order, $payment)->load(['paymentMethod', 'refunds']));
    }

    public function update(Request $request, Order $order, Payment $payment): JsonResponse
    {
        $payment = $this->ownedPayment($order, $payment);
        $payment->update($this->validated($request, true));

        return response()->json($payment->fresh('paymentMethod'));
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'payment_method_id' => ['sometimes', 'nullable', 'uuid', 'exists:payment_methods,id'],
            'status' => [$required, 'in:pending,authorized,captured,failed,voided'],
            'amount_cents' => [$required, 'integer', 'min:1'],
            'currency' => [$required, 'string', 'size:3'],
            'processor_reference' => ['sometimes', 'nullable', 'string', 'max:120', 'unique:payments,processor_reference'.($partial ? ','.$request->route('payment')->id : '')],
            'captured_at' => ['sometimes', 'nullable', 'date'],
        ]);
    }

    private function ownedPayment(Order $order, Payment $payment): Payment
    {
        return $order->payments()->findOrFail($payment->id);
    }
}
