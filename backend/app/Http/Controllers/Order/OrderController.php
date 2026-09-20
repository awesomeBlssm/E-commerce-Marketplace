<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Catalog\ProductVariant;
use App\Models\Order\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Creates and reads checkout snapshots without allowing historical orders to be rewritten. */
class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with('customer')
            ->withCount(['lines', 'addresses'])
            ->latest('placed_at');

        if ($request->user()->type !== 'admin') {
            $query->whereHas('customer', fn ($customerQuery) =>
                $customerQuery->where('user_id', $request->user()->id)
            );
        }

        return response()->json($query->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.variant_id' => ['required', 'uuid', 'distinct', 'exists:product_variants,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
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

        $customer = $request->user()->customer;

        if (! $customer) {
            return response()->json(['message' => 'A customer profile is required to place an order.'], 422);
        }

        $variants = ProductVariant::query()
            ->with(['product', 'optionValues'])
            ->whereIn('id', collect($input['lines'])->pluck('variant_id'))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($variants->count() !== collect($input['lines'])->pluck('variant_id')->unique()->count()) {
            throw ValidationException::withMessages([
                'lines' => 'One or more selected variants are unavailable.',
            ]);
        }

        $currency = $variants->first()->currency;
        $lines = collect($input['lines'])->map(function (array $line) use ($variants, $currency): array {
            $variant = $variants[$line['variant_id']];

            if ($variant->currency !== $currency) {
                throw ValidationException::withMessages([
                    'lines' => 'All order items must use the same currency.',
                ]);
            }

            $unitPrice = $variant->price_cents;
            $quantity = $line['quantity'];

            return [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'title' => $variant->product->title,
                'variant_title' => $variant->optionValues->pluck('value')->implode(' / ') ?: null,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPrice,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => $unitPrice * $quantity,
            ];
        });

        $subtotal = $lines->sum('total_cents');

        $order = DB::transaction(function () use ($customer, $input, $lines, $subtotal, $currency): Order {
            $order = Order::create([
                'customer_id' => $customer->id,
                'number' => 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                'status' => 'pending',
                'currency' => $currency,
                'subtotal_cents' => $subtotal,
                'discount_cents' => 0,
                'shipping_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => $subtotal,
                'placed_at' => now(),
            ]);

            $order->lines()->createMany($lines);
            $order->addresses()->createMany($input['addresses']);

            return $order;
        });

        return response()->json($order->load(['customer', 'lines', 'addresses']), 201);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['customer', 'lines.variant', 'addresses']));
    }
}
