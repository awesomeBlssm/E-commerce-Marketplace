<?php

namespace App\Models\ReverseFlow;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Refund records a processor reversal without mutating the original payment amount. */
class Refund extends Model
{
    use HasUuids;

    protected $table = 'refunds';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'return_id',
        'status',
        'amount_cents',
        'reason',
        'processor_reference',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, 'return_id');
    }
}
