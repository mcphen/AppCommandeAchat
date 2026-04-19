<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validations_dap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dap_id')->constrained('demandes_autorisation_paiement')->cascadeOnDelete();
            $table->foreignId('niveau_validation_id')->constrained('niveaux_validation');
            $table->foreignId('validateur_id')->constrained('users');
            // approuve, rejete
            $table->string('statut');
            $table->text('commentaire')->nullable();
            $table->timestamp('validated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validations_dap');
    }
};
