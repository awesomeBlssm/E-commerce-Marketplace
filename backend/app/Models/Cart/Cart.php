<?php

namespace App\Models\Cart;

use App\Models\Catalog\ProductVariant;
use App\Models\Shopper\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Cart holds live-priced variant selections for a customer or anonymous session. */
class Cart extends Model
{
    use HasUuids;

    protected $table = 'carts';

    protected $fillable = [
        'customer_id',
        'session_token',
        'status',
        'currency',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'cart_items', 'cart_id', 'variant_id')
            ->withPivot(['id', 'quantity', 'added_at']);
    }
}
