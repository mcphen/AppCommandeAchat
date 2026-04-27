<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_arbitrage_dap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_arbitrage_id')->constrained('sessions_arbitrage')->cascadeOnDelete();
            $table->foreignId('dap_id')->constrained('demandes_autorisation_paiement')->cascadeOnDelete();
            $table->decimal('score_moyen', 8, 4)->nullable();
            $table->unsignedSmallInteger('ordre_final')->nullable();
            $table->enum('statut', ['en_attente', 'selectionne', 'reporte'])->default('en_attente');
            $table->timestamps();
            $table->unique(['session_arbitrage_id', 'dap_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_arbitrage_dap');
    }
};
