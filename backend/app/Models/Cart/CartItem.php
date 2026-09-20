<?php

namespace App\Models\Cart;

use App\Models\Catalog\ProductVariant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** CartItem stores quantity only; price is always read from the current variant. */
class CartItem extends Model
{
    use HasUuids;

    protected $table = 'cart_items';

    public $timestamps = false;

    protected $fillable = [
        'cart_id',
        'variant_id',
        'quantity',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'added_at' => 'datetime',
        ];
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
