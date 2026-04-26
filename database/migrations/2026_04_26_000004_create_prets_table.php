<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained();
            $table->foreignId('compte_epargne_id')->constrained('comptes_epargne');
            $table->decimal('montant_demande', 15, 2);
            $table->decimal('montant_accorde', 15, 2)->nullable();
            $table->text('motif')->nullable();
            $table->enum('statut', ['draft', 'pending', 'approved', 'rejected', 'decaisse', 'solde'])->default('draft');
            $table->unsignedTinyInteger('current_level_order')->nullable();
            $table->foreignId('mode_reglement_id')->nullable()->constrained('modes_reglement');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decaisse_at')->nullable();
            $table->timestamp('solde_at')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prets');
    }
};
