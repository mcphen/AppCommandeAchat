<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPurchaseOrderDate extends Command
{
    protected $signature   = 'purchase-orders:backfill-order-date';
    protected $description = 'Remplit order_date avec created_at pour les commandes existantes sans date';

    public function handle(): int
    {
        $count = DB::table('purchase_orders')
            ->whereNull('order_date')
            ->whereNull('deleted_at')
            ->count();

        if ($count === 0) {
            $this->info('Aucune commande à mettre à jour.');
            return self::SUCCESS;
        }

        $this->info("$count commande(s) sans order_date détectée(s).");

        if (! $this->confirm('Remplir order_date avec created_at pour ces commandes ?', true)) {
            $this->warn('Opération annulée.');
            return self::SUCCESS;
        }

        $updated = DB::table('purchase_orders')
            ->whereNull('order_date')
            ->whereNull('deleted_at')
            ->update(['order_date' => DB::raw('DATE(created_at)')]);

        $this->info("$updated commande(s) mise(s) à jour avec succès.");

        return self::SUCCESS;
    }
}
