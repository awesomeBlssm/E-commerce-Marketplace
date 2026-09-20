<?php

namespace App\Models\Shipping;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** ShippingZone groups destinations that share delivery pricing rules. */
class ShippingZone extends Model
{
    use HasUuids;

    protected $table = 'shipping_zones';

    public $timestamps = false;

    protected $fillable = ['name', 'country_codes'];

    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'zone_id');
    }
}
