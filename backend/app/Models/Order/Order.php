<?php

namespace App\Models\Order;

use App\Models\Fulfillment\Shipment;
use App\Models\Money\Payment;
use App\Models\ReverseFlow\ReturnRequest;
use App\Models\Shopper\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Order stores the immutable monetary and customer snapshot agreed at checkout. */
class Order extends Model
{
    use HasUuids;

    protected $table = 'orders';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'number',
        'guest_access_token_hash',
        'status',
        'currency',
        'subtotal_cents',
        'discount_cents',
        'shipping_cents',
        'tax_cents',
        'total_cents',
        'placed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'shipping_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnRequest::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
