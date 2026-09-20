<?php

namespace Database\Seeders;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductImage;
use App\Models\Catalog\ProductOption;
use App\Models\Catalog\ProductOptionValue;
use App\Models\Catalog\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::factory()->seller()->create([
            'email' => 'seller@ecomarket.com',
        ]);

        $brands = Brand::factory()->count(3)->create();
        $categories = Category::factory()->count(6)->create();

        for ($index = 0; $index < 9; $index++) {
            $product = Product::factory()->create([
                'seller_id' => $seller->id,
                'brand_id' => $brands[$index % $brands->count()]->id,
            ]);

            $product->categories()->attach([
                $categories[$index % $categories->count()]->id,
            ]);

            $size = ProductOption::factory()->create([
                'product_id' => $product->id,
                'name' => 'Size',
                'position' => 1,
            ]);
            $color = ProductOption::factory()->create([
                'product_id' => $product->id,
                'name' => 'Color',
                'position' => 2,
            ]);

            $small = ProductOptionValue::factory()->create([
                'option_id' => $size->id,
                'value' => 'Small',
                'position' => 1,
            ]);
            $large = ProductOptionValue::factory()->create([
                'option_id' => $size->id,
                'value' => 'Large',
                'position' => 2,
            ]);
            $black = ProductOptionValue::factory()->create([
                'option_id' => $color->id,
                'value' => 'Black',
                'position' => 1,
            ]);

            foreach ([[$small, $black], [$large, $black]] as $variantIndex => $optionValues) {
                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'sku' => sprintf('DEMO-%03d-%d', $index + 1, $variantIndex + 1),
                ]);

                $variant->optionValues()->sync(collect($optionValues)->pluck('id'));
            }

            ProductImage::factory()->create([
                'product_id' => $product->id,
                'alt_text' => $product->title,
            ]);
        }
    }
}
