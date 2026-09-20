<?php

namespace App\Models\Shopper;

use App\Models\Catalog\Product;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use HasUuids;

    protected $table = 'product_reviews';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'customer_id',
        'rating',
        'title',
        'body',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
