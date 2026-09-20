<?php

namespace App\Http\Controllers\ReverseFlow;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\ReverseFlow\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Manages RMA lifecycle without mutating the original order snapshot. */
class ReturnRequestController extends Controller
{
    public function index(Order $order): JsonResponse
    {
        return response()->json($order->returns()->withCount(['lines', 'refunds'])->latest('requested_at')->get());
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        $returnRequest = $order->returns()->create($this->validated($request));

        return response()->json($returnRequest, 201);
    }

    public function show(Order $order, ReturnRequest $returnRequest): JsonResponse
    {
        return response()->json($this->ownedReturn($order, $returnRequest)->load(['lines.orderLine', 'refunds']));
    }

    public function update(Request $request, Order $order, ReturnRequest $returnRequest): JsonResponse
    {
        $returnRequest = $this->ownedReturn($order, $returnRequest);
        $returnRequest->update($this->validated($request, true));

        return response()->json($returnRequest->fresh(['lines.orderLine', 'refunds']));
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'rma_number' => [$required, 'string', 'max:32', 'unique:returns,rma_number'.($partial ? ','.$request->route('return')?->id : '')],
            'status' => [$required, 'in:requested,approved,received,rejected,closed'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'requested_at' => ['sometimes', 'date'],
            'received_at' => ['sometimes', 'nullable', 'date'],
        ]);
    }

    private function ownedReturn(Order $order, ReturnRequest $returnRequest): ReturnRequest
    {
        return $order->returns()->findOrFail($returnRequest->id);
    }
}
