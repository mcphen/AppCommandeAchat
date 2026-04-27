<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membres_comite_arbitrage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comite_arbitrage_id')->constrained('comites_arbitrage')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_membre', ['president', 'membre', 'secretaire'])->default('membre');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['comite_arbitrage_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membres_comite_arbitrage');
    }
};
