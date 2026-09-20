<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('product_id');

            $table->string('sku', 64)->unique();
            $table->string('barcode', 64)->nullable();

            $table->integer('price_cents');
            $table->integer('compare_at_cents')->nullable();

            $table->char('currency', 3);

            $table->integer('weight_grams')->nullable();

            $table->boolean('requires_shipping');
            $table->boolean('is_active');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->index([
                'product_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
