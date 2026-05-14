<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter les colonnes polymorphiques à validation_logs
        // pour supporter à la fois PurchaseOrder et DisbursementRequest
        Schema::table('validation_logs', function (Blueprint $table) {
            $table->nullableMorphs('validationable');
        });

        // Remplir les colonnes polymorphiques à partir de purchase_order_id existant
        \Illuminate\Support\Facades\DB::statement("
            UPDATE validation_logs
            SET validationable_type = 'App\\\\Models\\\\PurchaseOrder',
                validationable_id   = purchase_order_id
            WHERE purchase_order_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('validation_logs', function (Blueprint $table) {
            $table->dropMorphs('validationable');
        });
    }
};
