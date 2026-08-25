<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('reception_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('purchase_order_receptions')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('transferred_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('transferred_at');
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('reception_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('reception_transfers')->cascadeOnDelete();
            $table->foreignId('reception_line_id')->constrained('purchase_order_reception_lines')->restrictOnDelete();
            $table->decimal('quantity_transferred', 10, 2);
            $table->timestamps();
            $table->unique(['transfer_id', 'reception_line_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('reception_transfer_lines');
        Schema::dropIfExists('reception_transfers');
    }
};