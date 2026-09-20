<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('customer_id');

            $table->enum('type', [
                'billing',
                'shipping',
            ]);

            $table->string('line1', 255);
            $table->string('line2', 255)->nullable();
            $table->string('city', 120);
            $table->string('region', 120)->nullable();
            $table->string('postal_code', 20);
            $table->char('country_code', 2);

            $table->boolean('is_default')->default(false);

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();

            $table->index([
                'customer_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
