<?php

namespace App\Models\Shipping;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** ShippingRate describes a price rule bounded by order value and package weight. */
class ShippingRate extends Model
{
    use HasUuids;

    protected $table = 'shipping_rates';

    public $timestamps = false;

    protected $fillable = [
        'zone_id',
        'name',
        'price_cents',
        'currency',
        'min_order_cents',
        'max_order_cents',
        'min_weight_grams',
        'max_weight_grams',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'min_order_cents' => 'integer',
            'max_order_cents' => 'integer',
            'min_weight_grams' => 'integer',
            'max_weight_grams' => 'integer',
        ];
    }

    public function zone()
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }
}
