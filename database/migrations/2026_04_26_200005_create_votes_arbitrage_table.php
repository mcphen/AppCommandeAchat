<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes_arbitrage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_arbitrage_id')->constrained('sessions_arbitrage')->cascadeOnDelete();
            $table->foreignId('dap_id')->constrained('demandes_autorisation_paiement')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('rang');
            $table->text('commentaire')->nullable();
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();
            $table->unique(['session_arbitrage_id', 'dap_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes_arbitrage');
    }
};
