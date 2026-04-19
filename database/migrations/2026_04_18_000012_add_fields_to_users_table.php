<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->foreignId('entreprise_id')->nullable()->after('role_id')->constrained('entreprises')->nullOnDelete();
            $table->string('fonction')->nullable()->after('entreprise_id');
            $table->string('signature_path')->nullable()->after('fonction');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('entreprise_id');
            $table->dropColumn(['fonction', 'signature_path']);
        });
    }
};
