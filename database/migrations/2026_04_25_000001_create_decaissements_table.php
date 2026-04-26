<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decaissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users');
            $table->decimal('montant', 15, 2);
            $table->foreignId('mode_reglement_id')->constrained('modes_reglement');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->date('decaissement_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decaissements');
    }
};
