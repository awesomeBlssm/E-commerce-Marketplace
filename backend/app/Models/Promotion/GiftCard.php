<?php

namespace App\Models\Promotion;

use App\Models\Shopper\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** GiftCard stores a currency balance that is changed only through its transaction ledger. */
class GiftCard extends Model
{
    use HasUuids;

    protected $table = 'gift_cards';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'initial_balance_cents',
        'balance_cents',
        'currency',
        'status',
        'issued_to_customer_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance_cents' => 'integer',
            'balance_cents' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function issuedToCustomer()
    {
        return $this->belongsTo(Customer::class, 'issued_to_customer_id');
    }

    public function transactions()
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
}
