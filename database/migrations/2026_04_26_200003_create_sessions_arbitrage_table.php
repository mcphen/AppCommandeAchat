<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions_arbitrage', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('comite_arbitrage_id')->constrained('comites_arbitrage');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->decimal('tresorerie_disponible', 15, 2)->nullable();
            $table->boolean('bloquer_depassement')->default(true);
            $table->enum('statut', ['brouillon', 'en_vote', 'cloturee', 'annulee'])->default('brouillon');
            $table->date('date_ouverture')->nullable();
            $table->date('date_cloture')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('finalise_par')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_arbitrage');
    }
};
