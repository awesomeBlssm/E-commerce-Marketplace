<?php

namespace App\Http\Controllers\ReverseFlow;

use App\Http\Controllers\Controller;
use App\Models\Order\OrderLine;
use App\Models\ReverseFlow\ReturnLine;
use App\Models\ReverseFlow\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Records returned quantities and whether each item can be restocked. */
class ReturnLineController extends Controller
{
    public function index(ReturnRequest $returnRequest): JsonResponse
    {
        return response()->json($returnRequest->lines()->with('orderLine')->get());
    }

    public function store(Request $request, ReturnRequest $returnRequest): JsonResponse
    {
        $data = $request->validate([
            'order_line_id' => ['required', 'uuid', 'exists:order_lines,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'restock' => ['required', 'boolean'],
        ]);

        OrderLine::query()
            ->whereKey($data['order_line_id'])
            ->whereHas('order', fn ($query) => $query->whereKey($returnRequest->order_id))
            ->firstOrFail();

        $line = $returnRequest->lines()->create($data);

        return response()->json($line->load('orderLine'), 201);
    }

    public function show(ReturnRequest $returnRequest, ReturnLine $line): JsonResponse
    {
        return response()->json($returnRequest->lines()->findOrFail($line->id)->load('orderLine'));
    }
}
