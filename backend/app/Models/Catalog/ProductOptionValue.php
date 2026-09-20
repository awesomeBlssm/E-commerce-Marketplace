<?php

namespace App\Models\Catalog;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOptionValue extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_option_values';

    public $timestamps = false;

    protected $fillable = [
        'option_id',
        'value',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function option()
    {
        return $this->belongsTo(
            ProductOption::class,
            'option_id'
        );
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'variant_option_values',
            'option_value_id',
            'variant_id'
        );
    }
}
