<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_autorisation_paiement', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('expression_besoin_id')->constrained('expressions_besoin')->cascadeOnDelete();
            $table->foreignId('budget_annuel_id')->nullable()->constrained('budgets_annuels')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users'); // compta qui a transformé l'EB
            // en_cours, validee, rejetee, payee
            $table->string('statut')->default('en_cours');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_autorisation_paiement');
    }
};
