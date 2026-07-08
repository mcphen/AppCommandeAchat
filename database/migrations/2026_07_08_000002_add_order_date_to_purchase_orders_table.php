<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_orders', 'order_date')) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('order_date')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('purchase_orders', 'order_date')) {
            return;
        }

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('order_date');
        });
    }
};
