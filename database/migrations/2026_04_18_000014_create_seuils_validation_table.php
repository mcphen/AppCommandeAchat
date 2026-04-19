<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chaque ligne indique le montant minimum à partir duquel ce niveau de validation est requis.
        // Ex: DF=0 (toujours requis), DG=500000, PDG=2000000
        Schema::create('seuils_validation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('niveau_validation_id')->constrained('niveaux_validation')->cascadeOnDelete();
            $table->decimal('montant_seuil', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seuils_validation');
    }
};
