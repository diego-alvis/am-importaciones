<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->string('operator_name', 100)->nullable()->after('stand_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('operator_name', 100)->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropColumn('operator_name');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('operator_name');
        });
    }
};
