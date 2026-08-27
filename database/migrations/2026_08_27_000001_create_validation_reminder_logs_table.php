<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('validation_level_id')->constrained()->cascadeOnDelete();
            $table->timestamp('sent_at');
            $table->unsignedSmallInteger('waiting_business_days');
            $table->unsignedSmallInteger('recipients_count');
            $table->timestamps();
            $table->index(['purchase_order_id', 'validation_level_id', 'sent_at'], 'validation_reminder_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_reminder_logs');
    }
};
