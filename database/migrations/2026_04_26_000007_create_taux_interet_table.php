<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taux_interet', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->decimal('taux_annuel', 5, 2)->default(0.00);
            $table->enum('type', ['epargne', 'pret']);
            $table->boolean('is_active')->default(false);
            $table->date('applique_depuis')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taux_interet');
    }
};
