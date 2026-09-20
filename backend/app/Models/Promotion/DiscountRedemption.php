<?php

namespace App\Models\Promotion;

use App\Models\Order\Order;
use App\Models\Shopper\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** DiscountRedemption snapshots promotion usage against one order and customer. */
class DiscountRedemption extends Model
{
    use HasUuids;

    protected $table = 'discount_redemptions';

    public $timestamps = false;

    protected $fillable = [
        'discount_id',
        'order_id',
        'customer_id',
        'amount_cents',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'redeemed_at' => 'datetime',
        ];
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
