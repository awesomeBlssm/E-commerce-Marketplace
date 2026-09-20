<?php

namespace App\Models\Promotion;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Discount defines a promotion whose value is stored as an integer or percentage. */
class Discount extends Model
{
    use HasUuids;

    protected $table = 'discounts';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'type',
        'value',
        'currency',
        'minimum_spend_cents',
        'usage_limit',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'minimum_spend_cents' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function redemptions()
    {
        return $this->hasMany(DiscountRedemption::class);
    }
}
