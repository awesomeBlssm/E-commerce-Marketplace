<?php

namespace Database\Factories;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOption>
 */
class ProductOptionFactory extends Factory
{
    protected $model = ProductOption::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Size', 'Color', 'Material', 'Style']),
            'position' => fake()->numberBetween(1, 3),
        ];
    }
}
