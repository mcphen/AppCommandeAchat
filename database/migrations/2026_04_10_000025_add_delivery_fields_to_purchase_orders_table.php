<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('order_number')->nullable()->unique()->after('status');
            $table->string('delivery_status')->nullable()->after('order_number'); // ordered | partially_received | received
            $table->timestamp('ordered_at')->nullable()->after('delivery_status');
            $table->timestamp('fully_received_at')->nullable()->after('ordered_at');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['order_number', 'delivery_status', 'ordered_at', 'fully_received_at']);
        });
    }
};
