<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('parent_id')->nullable();

            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->integer('position');

            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();

            $table->index([
                'parent_id',
                'position',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
