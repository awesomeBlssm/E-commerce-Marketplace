<?php

namespace Database\Factories;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-????')),
            'barcode' => fake()->optional()->ean13(),
            'price_cents' => fake()->numberBetween(1500, 25000),
            'compare_at_cents' => null,
            'currency' => 'USD',
            'weight_grams' => fake()->numberBetween(100, 3000),
            'requires_shipping' => true,
            'is_active' => true,
        ];
    }
}
