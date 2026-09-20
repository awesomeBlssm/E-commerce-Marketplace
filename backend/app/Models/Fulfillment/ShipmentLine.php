<?php

namespace App\Models\Fulfillment;

use App\Models\Order\OrderLine;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** ShipmentLine records the quantity of one order line packed in a parcel. */
class ShipmentLine extends Model
{
    use HasUuids;

    protected $table = 'shipment_lines';

    public $timestamps = false;

    protected $fillable = [
        'shipment_id',
        'order_line_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLine::class);
    }
}
