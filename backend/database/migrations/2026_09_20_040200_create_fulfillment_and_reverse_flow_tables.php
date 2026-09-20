<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('warehouse_id');
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'failed']);
            $table->string('carrier', 80)->nullable();
            $table->string('tracking_number', 120)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->index('order_id');
            $table->index('status');
        });

        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shipment_id');
            $table->uuid('order_line_id');
            $table->integer('quantity');

            $table->foreign('shipment_id')->references('id')->on('shipments')->cascadeOnDelete();
            $table->foreign('order_line_id')->references('id')->on('order_lines')->restrictOnDelete();
            $table->unique(['shipment_id', 'order_line_id']);
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('rma_number', 32)->unique();
            $table->enum('status', ['requested', 'approved', 'received', 'rejected', 'closed']);
            $table->string('reason', 255)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('received_at')->nullable();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->index('order_id');
        });

        Schema::create('return_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('return_id');
            $table->uuid('order_line_id');
            $table->integer('quantity');
            $table->boolean('restock')->default(true);

            $table->foreign('return_id')->references('id')->on('returns')->cascadeOnDelete();
            $table->foreign('order_line_id')->references('id')->on('order_lines')->restrictOnDelete();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id');
            $table->uuid('return_id')->nullable();
            $table->enum('status', ['pending', 'succeeded', 'failed']);
            $table->integer('amount_cents');
            $table->string('reason', 255)->nullable();
            $table->string('processor_reference', 120)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // The payments module is added separately; payment_id is kept ready for that FK.
            $table->foreign('return_id')->references('id')->on('returns')->nullOnDelete();
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('return_lines');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('shipment_lines');
        Schema::dropIfExists('shipments');
    }
};
