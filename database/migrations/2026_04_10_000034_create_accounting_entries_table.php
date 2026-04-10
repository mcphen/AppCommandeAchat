<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->string('journal_code', 10)->default('ACH')->comment('Code journal (ACH = Achats)');
            $table->string('piece_ref', 50)->comment('Numéro du bon de commande (BC-2026-00001)');
            $table->string('account_code', 10)->comment('Numéro de compte OHADA');
            $table->string('account_label', 255)->comment('Libellé du compte');
            $table->string('aux_code', 50)->nullable()->comment('Code auxiliaire (code fournisseur)');
            $table->string('aux_label', 255)->nullable()->comment('Libellé auxiliaire (nom fournisseur)');
            $table->string('entry_label', 500)->comment('Libellé de l\'écriture');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['purchase_order_id']);
            $table->index(['entry_date']);
            $table->index(['account_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_entries');
    }
};
