<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete()->after('id');
            $table->foreignId('validation_level_id')->nullable()->constrained('validation_levels')->nullOnDelete()->after('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\ValidationLevel::class);
            $table->dropForeignIdFor(\App\Models\Role::class);
        });
    }
};
