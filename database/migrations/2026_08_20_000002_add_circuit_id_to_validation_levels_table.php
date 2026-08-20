<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('validation_levels', function (Blueprint $table) {
            $table->foreignId('circuit_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        $achatId = DB::table('circuits')->where('code', 'achat')->value('id');
        DB::table('validation_levels')->whereNull('circuit_id')->update(['circuit_id' => $achatId]);

        DB::statement('ALTER TABLE validation_levels MODIFY circuit_id BIGINT UNSIGNED NOT NULL');

        Schema::table('validation_levels', function (Blueprint $table) {
            $table->unique(['circuit_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::table('validation_levels', function (Blueprint $table) {
            $table->dropUnique(['circuit_id', 'order']);
            $table->dropConstrainedForeignId('circuit_id');
        });
    }
};
