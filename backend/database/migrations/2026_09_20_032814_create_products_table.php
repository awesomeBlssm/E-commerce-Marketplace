<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('brand_id')->nullable();

            $table->string('title', 255);
            $table->string('slug', 280)->unique();
            $table->text('description')->nullable();

            $table->enum('status', [
                'draft',
                'active',
                'archived',
            ]);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('brand_id')
                ->references('id')
                ->on('brands')
                ->nullOnDelete();

            $table->index([
                'status',
                'published_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
