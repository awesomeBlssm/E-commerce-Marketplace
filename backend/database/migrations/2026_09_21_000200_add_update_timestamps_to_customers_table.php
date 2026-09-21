<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('customers')->where('phone', '')->update(['phone' => null]);

        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('full_name_updated_at')->nullable()->after('full_name');
            $table->timestamp('email_updated_at')->nullable()->after('email');
            $table->timestamp('phone_updated_at')->nullable()->after('phone');
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['full_name_updated_at', 'email_updated_at', 'phone_updated_at']);
        });
    }
};
