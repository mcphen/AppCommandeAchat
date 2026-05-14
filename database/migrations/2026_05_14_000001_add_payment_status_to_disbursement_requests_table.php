<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disbursement_requests', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('status'); // null = en attente, 'paid' = payé
        });
    }

    public function down(): void
    {
        Schema::table('disbursement_requests', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
