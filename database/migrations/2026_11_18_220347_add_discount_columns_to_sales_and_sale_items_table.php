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
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 10, 2)->nullable()->after('customer_id');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->after('subtotal_amount');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('original_price', 10, 2)->nullable()->after('quantity');
            $table->decimal('unit_discount', 10, 2)->default(0.00)->after('original_price');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->after('unit_discount');
        });

        // Inicializa las ventas históricas para mantener consistencia contable
        DB::table('sales')
            ->whereNull('subtotal_amount')
            ->update(['subtotal_amount' => DB::raw('total_amount')]);

        DB::table('sale_items')
            ->whereNull('original_price')
            ->update(['original_price' => DB::raw('unit_price')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'unit_discount', 'discount_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'discount_amount']);
        });
    }
};
