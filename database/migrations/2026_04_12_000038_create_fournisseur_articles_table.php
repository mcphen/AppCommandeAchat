<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseur_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_price', 15, 2);
            $table->string('reference_fournisseur', 100)->nullable();
            $table->unsignedSmallInteger('delai_livraison_jours')->nullable();
            $table->date('valide_jusqu_au')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['fournisseur_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fournisseur_articles');
    }
};
