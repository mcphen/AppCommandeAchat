<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dap_id')->constrained('demandes_autorisation_paiement')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->decimal('montant', 15, 2);
            $table->date('date_paiement');
            $table->string('reference')->nullable();
            // virement, cheque, espece, mobile_money
            $table->string('mode_paiement');
            $table->string('banque')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
