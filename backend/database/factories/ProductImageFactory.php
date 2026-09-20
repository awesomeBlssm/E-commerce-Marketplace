<?php

namespace Database\Factories;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'variant_id' => null,
            'url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200',
            'alt_text' => fake()->sentence(4),
            'position' => 1,
        ];
    }
}
