<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('validation_logs', function (Blueprint $table) {
            $table->foreignId('delegated_by_id')
                ->nullable()
                ->after('comment')
                ->constrained('users')
                ->noActionOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('validation_logs', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'delegated_by_id');
            $table->dropColumn('delegated_by_id');
        });
    }
};
