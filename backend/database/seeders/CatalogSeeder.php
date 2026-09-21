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
use Illuminate\Support\Facades\Hash;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $seller = User::query()->firstOrCreate(
            ['email' => 'seller@ecomarket.com'],
            [
                'password_hash' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
                'type' => 'seller',
            ]
        );
        $seller->update(['type' => 'seller', 'status' => 'active']);

        $brands = collect([
            ['name' => 'Northstar Goods', 'slug' => 'northstar-goods'],
            ['name' => 'Cedar & Co', 'slug' => 'cedar-and-co'],
            ['name' => 'Morrow Supply', 'slug' => 'morrow-supply'],
        ])->map(fn (array $brand) => Brand::query()->updateOrCreate(
            ['slug' => $brand['slug']],
            [...$brand, 'description' => 'Demo catalog brand.']
        ));

        $categories = collect([
            ['name' => 'Everyday Carry', 'slug' => 'everyday-carry'],
            ['name' => 'Home Office', 'slug' => 'home-office'],
            ['name' => 'Travel Essentials', 'slug' => 'travel-essentials'],
            ['name' => 'Outdoor Living', 'slug' => 'outdoor-living'],
            ['name' => 'Audio and Tech', 'slug' => 'audio-and-tech'],
            ['name' => 'Gifts', 'slug' => 'gifts'],
        ])->map(fn (array $category, int $index) => Category::query()->updateOrCreate(
            ['slug' => $category['slug']],
            [...$category, 'parent_id' => null, 'position' => $index + 1]
        ));

        for ($index = 0; $index < 9; $index++) {
            $productNumber = $index + 1;
            $product = Product::query()->updateOrCreate(
                ['slug' => "demo-product-{$productNumber}"],
                [
                    'seller_id' => $seller->id,
                    'brand_id' => $brands[$index % $brands->count()]->id,
                    'title' => "Demo Product {$productNumber}",
                    'description' => 'A seeded product for local development.',
                    'status' => 'active',
                    'published_at' => now(),
                    'created_at' => now(),
                ]
            );

            $product->categories()->sync([
                $categories[$index % $categories->count()]->id,
            ]);

            $size = ProductOption::query()->updateOrCreate(
                ['product_id' => $product->id, 'name' => 'Size'],
                ['position' => 1]
            );
            $color = ProductOption::query()->updateOrCreate(
                ['product_id' => $product->id, 'name' => 'Color'],
                ['position' => 2]
            );

            $small = ProductOptionValue::query()->updateOrCreate(
                ['option_id' => $size->id, 'value' => 'Small'],
                ['position' => 1]
            );
            $large = ProductOptionValue::query()->updateOrCreate(
                ['option_id' => $size->id, 'value' => 'Large'],
                ['position' => 2]
            );
            $black = ProductOptionValue::query()->updateOrCreate(
                ['option_id' => $color->id, 'value' => 'Black'],
                ['position' => 1]
            );

            foreach ([[$small, $black], [$large, $black]] as $variantIndex => $optionValues) {
                $variant = ProductVariant::query()->updateOrCreate(
                    ['sku' => sprintf('DEMO-%03d-%d', $productNumber, $variantIndex + 1)],
                    [
                        'product_id' => $product->id,
                        'price_cents' => 499999 + ($index * 500),
                        'currency' => 'PHP',
                        'weight_grams' => 500,
                        'requires_shipping' => true,
                        'is_active' => true,
                    ]
                );

                $variant->optionValues()->sync(collect($optionValues)->pluck('id'));
            }

            ProductImage::query()->updateOrCreate(
                ['product_id' => $product->id, 'variant_id' => null, 'position' => 1],
                [
                    'url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1200',
                    'alt_text' => $product->title,
                ]
            );
        }
    }
}
