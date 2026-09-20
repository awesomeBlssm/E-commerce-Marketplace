<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->string('number', 32)->unique();
            $table->string('guest_access_token_hash', 255)->nullable();
            $table->enum('status', ['pending', 'paid', 'partially_fulfilled', 'fulfilled', 'cancelled', 'refunded']);
            $table->char('currency', 3);
            $table->integer('subtotal_cents');
            $table->integer('discount_cents');
            $table->integer('shipping_cents');
            $table->integer('tax_cents');
            $table->integer('total_cents');
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->index(['customer_id', 'placed_at']);
            $table->index(['status', 'placed_at']);
        });

        Schema::create('order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('variant_id');
            $table->string('sku', 64);
            $table->string('title', 255);
            $table->string('variant_title', 255)->nullable();
            $table->integer('quantity');
            $table->integer('unit_price_cents');
            $table->integer('discount_cents');
            $table->integer('tax_cents');
            $table->integer('total_cents');

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->restrictOnDelete();
            $table->index('order_id');
        });

        Schema::create('order_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->enum('type', ['billing', 'shipping']);
            $table->string('full_name', 120);
            $table->string('line1', 255);
            $table->string('line2', 255)->nullable();
            $table->string('city', 120);
            $table->string('region', 120)->nullable();
            $table->string('postal_code', 20);
            $table->char('country_code', 2);
            $table->string('phone', 32)->nullable();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unique(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_lines');
        Schema::dropIfExists('orders');
    }
};
