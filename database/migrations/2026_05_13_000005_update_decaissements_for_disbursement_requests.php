<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decaissements', function (Blueprint $table) {
            // Rendre purchase_order_id nullable pour les décaissements liés aux DD
            $table->foreignId('purchase_order_id')->nullable()->change();

            // Lien vers la demande de décaissement
            $table->foreignId('disbursement_request_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('disbursement_requests')
                ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('decaissements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disbursement_request_id');
            $table->foreignId('purchase_order_id')->nullable(false)->change();
        });
    }
};
