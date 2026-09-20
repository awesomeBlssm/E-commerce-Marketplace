<?php

namespace App\Models\Fulfillment;

use App\Models\Inventory\Warehouse;
use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Shipment represents one parcel dispatched from one warehouse for an order. */
class Shipment extends Model
{
    use HasUuids;

    protected $table = 'shipments';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'warehouse_id',
        'status',
        'carrier',
        'tracking_number',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines()
    {
        return $this->hasMany(ShipmentLine::class);
    }
}
