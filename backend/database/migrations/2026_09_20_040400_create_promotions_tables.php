<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();
            $table->enum('type', ['percentage', 'fixed_amount', 'free_shipping']);
            $table->integer('value');
            $table->char('currency', 3)->nullable();
            $table->integer('minimum_spend_cents')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('discount_redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('discount_id');
            $table->uuid('order_id');
            $table->uuid('customer_id');
            $table->integer('amount_cents');
            $table->timestamp('redeemed_at')->useCurrent();

            $table->foreign('discount_id')->references('id')->on('discounts')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->unique(['discount_id', 'order_id']);
            $table->index(['discount_id', 'customer_id']);
        });

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();
            $table->integer('initial_balance_cents');
            $table->integer('balance_cents');
            $table->char('currency', 3);
            $table->enum('status', ['active', 'redeemed', 'expired', 'cancelled']);
            $table->uuid('issued_to_customer_id')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreign('issued_to_customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('gift_card_id');
            $table->uuid('order_id')->nullable();
            $table->integer('amount_cents');
            $table->timestamp('occurred_at')->useCurrent();

            $table->foreign('gift_card_id')->references('id')->on('gift_cards')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->index(['gift_card_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_card_transactions');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('discount_redemptions');
        Schema::dropIfExists('discounts');
    }
};
