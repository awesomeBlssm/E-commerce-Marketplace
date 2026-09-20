<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WishlistItem extends Pivot
{
    protected $table = 'wishlist_items';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'wishlist_id',
        'variant_id',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public function wishlist()
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
