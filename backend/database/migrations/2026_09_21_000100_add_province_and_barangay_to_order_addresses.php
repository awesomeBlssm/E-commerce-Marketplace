<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->string('province', 120)->nullable()->after('city');
            $table->string('barangay', 120)->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->dropColumn(['province', 'barangay']);
        });
    }
};