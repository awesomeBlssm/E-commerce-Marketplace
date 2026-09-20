<?php

namespace App\Models\Shopper;

use App\Models\Catalog\ProductVariant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasUuids;

    protected $table = 'wishlists';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'name',
        'is_public',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'wishlist_items',
            'wishlist_id',
            'variant_id'
        )->withPivot('added_at');
    }
}
