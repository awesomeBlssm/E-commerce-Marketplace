<?php

namespace App\Models\Shopper;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasUuids;

    protected $table = 'customer_addresses';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'type',
        'line1',
        'line2',
        'city',
        'province',
        'barangay',
        'region',
        'postal_code',
        'country_code',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
