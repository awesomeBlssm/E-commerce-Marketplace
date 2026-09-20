<?php

namespace App\Models\Promotion;

use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** GiftCardTransaction is the signed append-only ledger for a gift-card balance. */
class GiftCardTransaction extends Model
{
    use HasUuids;

    protected $table = 'gift_card_transactions';

    public $timestamps = false;

    protected $fillable = [
        'gift_card_id',
        'order_id',
        'amount_cents',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
