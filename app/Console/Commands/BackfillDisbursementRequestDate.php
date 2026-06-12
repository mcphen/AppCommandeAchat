<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDisbursementRequestDate extends Command
{
    protected $signature   = 'disbursement-requests:backfill-order-date';
    protected $description = 'Remplit order_date avec created_at pour les demandes de décaissement existantes sans date';

    public function handle(): int
    {
        $count = DB::table('disbursement_requests')
            ->whereNull('order_date')
            ->whereNull('deleted_at')
            ->count();

        if ($count === 0) {
            $this->info('Aucune demande à mettre à jour.');
            return self::SUCCESS;
        }

        $this->info("$count demande(s) sans order_date détectée(s).");

        if (! $this->confirm('Remplir order_date avec created_at pour ces demandes ?', true)) {
            $this->warn('Opération annulée.');
            return self::SUCCESS;
        }

        $updated = DB::table('disbursement_requests')
            ->whereNull('order_date')
            ->whereNull('deleted_at')
            ->update(['order_date' => DB::raw('DATE(created_at)')]);

        $this->info("$updated demande(s) mise(s) à jour avec succès.");

        return self::SUCCESS;
    }
}
