<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Warehouse stores physical stock locations used to fulfil orders. */
class Warehouse extends Model
{
    use HasUuids;

    protected $table = 'warehouses';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'country_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }
}
