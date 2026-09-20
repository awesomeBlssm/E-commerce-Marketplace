<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('option_id');

            $table->string('value', 80);
            $table->integer('position');

            $table->unique([
                'option_id',
                'value',
            ]);

            $table->foreign('option_id')
                ->references('id')
                ->on('product_options')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
