<?php

namespace App\Models\ReverseFlow;

use App\Models\Order\OrderLine;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** ReturnLine records returned quantity and whether it may re-enter sellable stock. */
class ReturnLine extends Model
{
    use HasUuids;

    protected $table = 'return_lines';

    public $timestamps = false;

    protected $fillable = [
        'return_id',
        'order_line_id',
        'quantity',
        'restock',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'restock' => 'boolean',
        ];
    }

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, 'return_id');
    }

    public function orderLine()
    {
        return $this->belongsTo(OrderLine::class);
    }
}
