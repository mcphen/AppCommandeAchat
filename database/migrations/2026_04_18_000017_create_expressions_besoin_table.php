<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expressions_besoin', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('entreprise_id')->constrained('entreprises')->cascadeOnDelete();
            $table->string('objet');
            $table->text('description');
            $table->decimal('montant', 15, 2);
            $table->string('beneficiaire')->nullable();
            $table->text('justification')->nullable();
            // en_attente, validee, rejetee
            $table->string('statut')->default('en_attente');
            $table->text('motif_rejet')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expressions_besoin');
    }
};
