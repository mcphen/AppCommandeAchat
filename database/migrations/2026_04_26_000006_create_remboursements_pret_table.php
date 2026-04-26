<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursements_pret', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pret_id')->constrained()->cascadeOnDelete();
            $table->decimal('montant', 15, 2);
            $table->foreignId('mode_reglement_id')->constrained('modes_reglement');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('remboursement_date');
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements_pret');
    }
};
