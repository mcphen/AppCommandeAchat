<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','pending','needs_revision','approved','rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Remettre les eventuelles lignes needs_revision en pending avant de retirer la valeur
        DB::table('purchase_orders')->where('status', 'needs_revision')->update(['status' => 'pending']);
        DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft'");
    }
};
