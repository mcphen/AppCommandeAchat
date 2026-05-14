<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disbursement_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique()->nullable();
            $table->foreignId('user_id')->constrained()->noActionOnDelete();
            $table->foreignId('boutique_id')->nullable()->constrained()->noActionOnDelete();
            $table->foreignId('nature_operation_id')->constrained('nature_operations')->noActionOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'needs_revision', 'cancelled'])->default('draft');
            $table->unsignedTinyInteger('current_level_order')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursement_requests');
    }
};
