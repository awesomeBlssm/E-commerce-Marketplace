<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Creates and reads checkout snapshots without allowing historical orders to be rewritten. */
class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Order::query()
            ->with('customer')
            ->withCount(['lines', 'addresses'])
            ->latest('placed_at')
            ->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'number' => ['required', 'string', 'max:32', 'unique:orders,number'],
            'guest_access_token_hash' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,partially_fulfilled,fulfilled,cancelled,refunded'],
            'currency' => ['required', 'string', 'size:3'],
            'subtotal_cents' => ['required', 'integer', 'min:0'],
            'discount_cents' => ['required', 'integer', 'min:0'],
            'shipping_cents' => ['required', 'integer', 'min:0'],
            'tax_cents' => ['required', 'integer', 'min:0'],
            'total_cents' => ['required', 'integer', 'min:0'],
            'placed_at' => ['sometimes', 'date'],
            'cancelled_at' => ['sometimes', 'nullable', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.variant_id' => ['required', 'uuid', 'exists:product_variants,id'],
            'lines.*.sku' => ['required', 'string', 'max:64'],
            'lines.*.title' => ['required', 'string', 'max:255'],
            'lines.*.variant_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price_cents' => ['required', 'integer', 'min:0'],
            'lines.*.discount_cents' => ['required', 'integer', 'min:0'],
            'lines.*.tax_cents' => ['required', 'integer', 'min:0'],
            'lines.*.total_cents' => ['required', 'integer', 'min:0'],
            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.type' => ['required', 'in:billing,shipping'],
            'addresses.*.full_name' => ['required', 'string', 'max:120'],
            'addresses.*.line1' => ['required', 'string', 'max:255'],
            'addresses.*.line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required', 'string', 'max:120'],
            'addresses.*.region' => ['sometimes', 'nullable', 'string', 'max:120'],
            'addresses.*.postal_code' => ['required', 'string', 'max:20'],
            'addresses.*.country_code' => ['required', 'string', 'size:2'],
            'addresses.*.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);

        $order = DB::transaction(function () use ($data): Order {
            $lines = $data['lines'];
            $addresses = $data['addresses'];
            unset($data['lines'], $data['addresses']);

            $order = Order::create($data);
            $order->lines()->createMany($lines);
            $order->addresses()->createMany($addresses);

            return $order;
        });

        return response()->json($order->load(['customer', 'lines', 'addresses']), 201);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['customer', 'lines.variant', 'addresses']));
    }
}
