<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** OrderAddress freezes billing or shipping details used for the original order. */
class OrderAddress extends Model
{
    use HasUuids;

    protected $table = 'order_addresses';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'type',
        'full_name',
        'line1',
        'line2',
        'city',
        'province',
        'barangay',
        'region',
        'postal_code',
        'country_code',
        'phone',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
