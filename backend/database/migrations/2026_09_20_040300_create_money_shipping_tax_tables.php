<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->string('brand', 40)->nullable();
            $table->char('last4', 4)->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->string('processor_token', 255);
            $table->boolean('is_default')->default(false);

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->index(['customer_id', 'is_default']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('payment_method_id')->nullable();
            $table->enum('status', ['pending', 'authorized', 'captured', 'failed', 'voided']);
            $table->integer('amount_cents');
            $table->char('currency', 3);
            $table->string('processor_reference', 120)->nullable()->unique();
            $table->timestamp('captured_at')->nullable();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->index(['order_id', 'status']);
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->text('country_codes');
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('zone_id');
            $table->string('name', 120);
            $table->integer('price_cents');
            $table->char('currency', 3);
            $table->integer('min_order_cents')->nullable();
            $table->integer('max_order_cents')->nullable();
            $table->integer('min_weight_grams')->nullable();
            $table->integer('max_weight_grams')->nullable();

            $table->foreign('zone_id')->references('id')->on('shipping_zones')->cascadeOnDelete();
            $table->index('zone_id');
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('country_code', 2);
            $table->string('region', 120)->nullable();
            $table->string('name', 120);
            $table->integer('rate_basis_points');

            $table->index(['country_code', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};
