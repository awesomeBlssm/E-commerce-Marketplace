<?php

namespace Database\Factories;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'brand_id' => Brand::factory(),
            'seller_id' => User::factory()->seller(),
            'title' => ucwords($title),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->paragraphs(2, true),
            'status' => 'active',
            'published_at' => now(),
            'created_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}
