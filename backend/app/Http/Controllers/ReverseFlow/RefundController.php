<?php

namespace App\Http\Controllers\ReverseFlow;

use App\Http\Controllers\Controller;
use App\Models\ReverseFlow\Refund;
use App\Models\ReverseFlow\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Records refund attempts against a return without changing payment history. */
class RefundController extends Controller
{
    public function index(ReturnRequest $returnRequest): JsonResponse
    {
        return response()->json($returnRequest->refunds()->latest('created_at')->get());
    }

    public function store(Request $request, ReturnRequest $returnRequest): JsonResponse
    {
        $refund = $returnRequest->refunds()->create($request->validate([
            'payment_id' => ['required', 'uuid'],
            'status' => ['required', 'in:pending,succeeded,failed'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'processor_reference' => ['sometimes', 'nullable', 'string', 'max:120'],
            'created_at' => ['sometimes', 'date'],
        ]));

        return response()->json($refund, 201);
    }

    public function show(ReturnRequest $returnRequest, Refund $refund): JsonResponse
    {
        return response()->json($returnRequest->refunds()->findOrFail($refund->id));
    }
}
