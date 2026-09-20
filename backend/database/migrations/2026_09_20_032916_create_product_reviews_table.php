<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('product_id');
            $table->uuid('customer_id');

            $table->unsignedTinyInteger('rating');

            $table->string('title', 160)->nullable();
            $table->text('body')->nullable();

            $table->enum('status', [
                'pending',
                'published',
                'rejected',
            ])->default('pending');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();

            $table->index([
                'product_id',
                'status',
            ]);

            $table->unique([
                'product_id',
                'customer_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
