<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('sage_reference')->nullable()->unique()->after('order_number');
            $table->enum('source', ['manual', 'sage'])->default('manual')->after('sage_reference');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['sage_reference', 'source']);
        });
    }
};
