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
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->foreignId('reception_id')
                  ->nullable()
                  ->after('purchase_order_id')
                  ->constrained('purchase_order_receptions')
                  ->nullOnDelete();

            $table->index('reception_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropForeign(['reception_id']);
            $table->dropIndex(['reception_id']);
            $table->dropColumn('reception_id');
        });
    }
};
