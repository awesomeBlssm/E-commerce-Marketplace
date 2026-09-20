<?php

namespace Database\Factories;

use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    protected $model = ProductOptionValue::class;

    public function definition(): array
    {
        return [
            'option_id' => ProductOption::factory(),
            'value' => fake()->unique()->word(),
            'position' => fake()->numberBetween(1, 5),
        ];
    }
}
