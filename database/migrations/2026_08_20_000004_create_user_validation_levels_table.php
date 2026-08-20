<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_validation_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('validation_level_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'validation_level_id']);
        });

        DB::statement(
            'INSERT INTO user_validation_levels (user_id, validation_level_id, created_at, updated_at)
             SELECT id, validation_level_id, NOW(), NOW() FROM users WHERE validation_level_id IS NOT NULL'
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validation_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('validation_level_id')->nullable()->after('role_id')->constrained('validation_levels')->nullOnDelete();
        });

        DB::statement(
            'UPDATE users u
             JOIN user_validation_levels uvl ON uvl.user_id = u.id
             SET u.validation_level_id = uvl.validation_level_id'
        );

        Schema::dropIfExists('user_validation_levels');
    }
};
