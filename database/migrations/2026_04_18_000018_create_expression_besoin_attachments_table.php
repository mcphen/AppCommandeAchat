<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expression_besoin_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expression_besoin_id')->constrained('expressions_besoin')->cascadeOnDelete();
            $table->string('nom_fichier');
            $table->string('chemin');
            $table->string('type_mime')->nullable();
            $table->unsignedBigInteger('taille')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expression_besoin_attachments');
    }
};
