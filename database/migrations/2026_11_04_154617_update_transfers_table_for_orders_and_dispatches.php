<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('status', 30)->default('solicitado')->change();
            $table->foreignId('requested_by')->nullable()->after('to_location_id')->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('status');
        });

        DB::table('transfers')->where('status', 'pendiente')->update(['status' => 'solicitado']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['dispatched_by']);
            $table->dropColumn(['requested_by', 'dispatched_by', 'notes']);
        });
    }
};
