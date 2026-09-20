<?php

namespace App\Models\Order;

use App\Models\Catalog\ProductVariant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** OrderLine snapshots product identity and money so later catalog edits cannot alter an invoice. */
class OrderLine extends Model
{
    use HasUuids;

    protected $table = 'order_lines';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'variant_id',
        'sku',
        'title',
        'variant_title',
        'quantity',
        'unit_price_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'discount_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
