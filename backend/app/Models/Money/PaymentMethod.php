<?php

namespace App\Models\Money;

use App\Models\Shopper\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** PaymentMethod stores processor references and display-safe card fragments, never raw card data. */
class PaymentMethod extends Model
{
    use HasUuids;

    protected $table = 'payment_methods';

    public $timestamps = false;

    protected $hidden = ['processor_token'];

    protected $fillable = [
        'customer_id',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'processor_token',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'exp_month' => 'integer',
            'exp_year' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
