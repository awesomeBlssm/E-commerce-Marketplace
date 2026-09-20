<?php

namespace App\Models\ReverseFlow;

use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** ReturnRequest tracks an RMA and its lifecycle without changing the original order. */
class ReturnRequest extends Model
{
    use HasUuids;

    protected $table = 'returns';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'rma_number',
        'status',
        'reason',
        'requested_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function lines()
    {
        return $this->hasMany(ReturnLine::class, 'return_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'return_id');
    }
}
