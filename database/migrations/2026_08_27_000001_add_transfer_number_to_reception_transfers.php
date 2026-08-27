<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('reception_transfers', function (Blueprint $table) { $table->string('transfer_number', 20)->nullable()->unique()->after('id'); });
        DB::table('reception_transfers')->orderBy('id')->get(['id','transferred_at'])->each(function ($transfer) {
            DB::table('reception_transfers')->where('id',$transfer->id)->update(['transfer_number'=>sprintf('BT-%s-%06d',date('Y',strtotime($transfer->transferred_at)),$transfer->id)]);
        });
    }
    public function down(): void { Schema::table('reception_transfers', fn (Blueprint $table) => $table->dropColumn('transfer_number')); }
};