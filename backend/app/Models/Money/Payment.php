<?php

namespace App\Models\Money;

use App\Models\Order\Order;
use App\Models\ReverseFlow\Refund;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Payment records a processor attempt against an order using integer minor units. */
class Payment extends Model
{
    use HasUuids;

    protected $table = 'payments';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'status',
        'amount_cents',
        'currency',
        'processor_reference',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'captured_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
}
