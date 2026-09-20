<?php

namespace App\Models\Inventory;

use App\Models\Catalog\ProductVariant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** InventoryItem tracks on-hand and reserved stock for one variant in one warehouse. */
class InventoryItem extends Model
{
    use HasUuids;

    protected $table = 'inventory_items';

    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'warehouse_id',
        'on_hand',
        'reserved',
        'reorder_point',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'integer',
            'reserved' => 'integer',
            'reorder_point' => 'integer',
        ];
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
