<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('reference', 30)->nullable()->unique()->after('uuid');
            $table->softDeletes();
        });

        // Générer uuid + référence pour les lignes existantes
        $orders = DB::table('purchase_orders')->get(['id', 'created_at']);

        $counters = [];

        foreach ($orders as $order) {
            $year = date('Y', strtotime($order->created_at));

            if (! isset($counters[$year])) {
                $counters[$year] = DB::table('purchase_orders')
                    ->whereYear('created_at', $year)
                    ->where('id', '<', $order->id)
                    ->count() + 1;
            } else {
                $counters[$year]++;
            }

            $reference = 'DA-' . $year . '-' . str_pad($counters[$year], 5, '0', STR_PAD_LEFT);

            DB::table('purchase_orders')
                ->where('id', $order->id)
                ->update([
                    'uuid'      => Str::uuid()->toString(),
                    'reference' => $reference,
                ]);
        }

        // Rendre les colonnes non-nullables après remplissage
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->string('reference', 30)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'reference']);
            $table->dropSoftDeletes();
        });
    }
};
