<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('circuit_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
        });

        $achatId = DB::table('circuits')->where('code', 'achat')->value('id');
        DB::table('purchase_orders')->whereNull('circuit_id')->update(['circuit_id' => $achatId]);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('circuit_id');
        });
    }
};
