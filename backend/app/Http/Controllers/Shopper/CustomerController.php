<?php

namespace App\Http\Controllers\Shopper;

use App\Http\Controllers\Controller;
use App\Models\Shopper\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Customer::query()->paginate());
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->has('phone')) {
            $request->merge(['phone' => preg_replace('/[\s\-]/', '', (string) $request->input('phone')) ?: null]);
        }

        $customer = Customer::create($request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'full_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'regex:/^(\+?[0-9]{10,15})$/', 'unique:customers,phone'],
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

        if ($request->has('phone')) {
            $request->merge(['phone' => preg_replace('/[\s\-]/', '', (string) $request->input('phone')) ?: null]);
        }

        $validated = $request->validate([
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                $userId ? Rule::unique('users', 'email')->ignore($userId) : Rule::unique('users', 'email'),
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^(\+?[0-9]{10,15})$/',
                Rule::unique('customers', 'phone')->ignore($customer->id),
            ],
            'accepts_marketing' => ['sometimes', 'boolean'],
        ]);

        $cooldownDays = 30;
        $errors = [];

        // Check full_name 30-day restriction
        if (array_key_exists('full_name', $validated) && $validated['full_name'] !== $customer->full_name) {
            if ($customer->full_name_updated_at && $customer->full_name_updated_at->copy()->addDays($cooldownDays)->isFuture()) {
                $unlockDate = $customer->full_name_updated_at->copy()->addDays($cooldownDays)->toFormattedDateString();
                $errors['full_name'] = ["Full name can only be updated once a month. You can update it again on {$unlockDate}."];
            } else {
                $validated['full_name_updated_at'] = now();
            }
        }

        // Check email 30-day restriction
        if (array_key_exists('email', $validated) && $validated['email'] !== $customer->email) {
            if ($customer->email_updated_at && $customer->email_updated_at->copy()->addDays($cooldownDays)->isFuture()) {
                $unlockDate = $customer->email_updated_at->copy()->addDays($cooldownDays)->toFormattedDateString();
                $errors['email'] = ["Email can only be updated once a month. You can update it again on {$unlockDate}."];
            } else {
                $validated['email_updated_at'] = now();
            }
        }

        // Check phone 30-day restriction
        if (array_key_exists('phone', $validated) && $validated['phone'] !== $customer->phone) {
            if ($customer->phone_updated_at && $customer->phone_updated_at->copy()->addDays($cooldownDays)->isFuture()) {
                $unlockDate = $customer->phone_updated_at->copy()->addDays($cooldownDays)->toFormattedDateString();
                $errors['phone'] = ["Contact number can only be updated once a month. You can update it again on {$unlockDate}."];
            } else {
                $validated['phone_updated_at'] = now();
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($customer, $validated) {
            $customer->update($validated);

            if (isset($validated['email']) && $customer->user) {
                $customer->user->update([
                    'email' => $validated['email'],
                ]);
            }
        });

        return response()->json($customer->fresh(['user']));
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(null, 204);
    }
}
