<?php

namespace App\Http\Middleware;

use App\Models\Catalog\Product;
use App\Models\Order\Order;
use App\Models\Shopper\Customer;
use App\Models\Shopper\Wishlist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResourceOwner
{
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        if ($user->type === 'admin') {
            return $next($request);
        }

        $model = $request->route($resource);
        $isOwner = match ($resource) {
            'product' => $model instanceof Product && $model->seller_id === $user->id,
            'customer' => $model instanceof Customer && $model->user_id === $user->id,
            'order' => $model instanceof Order && $model->loadMissing('customer')->customer?->user_id === $user->id,
            'wishlist' => $model instanceof Wishlist && $model->loadMissing('customer')->customer?->user_id === $user->id,
            default => false,
        };

        if (! $isOwner) {
            return response()->json(['message' => 'You are not authorized to access this resource.'], 403);
        }

        return $next($request);
    }
}
