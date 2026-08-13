<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class BackfillPurchaseOrderProjects extends Command
{
    /**
     * Rattache project_id sur les commandes deja synchronisees AVANT l'ajout de la table
     * `projects` (ou synchronisees pendant que -ProjectCodeColumn etait desactive puis
     * jamais re-modifiees dans Sage depuis : le watermark de Sync-PurchaseOrders.ps1 ne les
     * refera jamais passer dans le webhook, donc project_code resterait rempli mais
     * project_id resterait null indefiniment sans ce backfill). Idempotent.
     */
    protected $signature = 'sage:backfill-projects {--dry-run : Affiche ce qui serait corrige sans rien modifier}';

    protected $description = 'Rattache les commandes existantes ayant un project_code brut a la table projects';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $orders = PurchaseOrder::whereNotNull('project_code')
            ->where('project_code', '!=', '')
            ->whereNull('project_id')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande a corriger.');

            return self::SUCCESS;
        }

        $this->info("{$orders->count()} commande(s) avec un project_code sans project_id.");

        foreach ($orders as $order) {
            $this->line(" - {$order->sage_reference} : project_code \"{$order->project_code}\"");

            if (! $dryRun) {
                $project = Project::findOrCreateFromCode($order->project_code);
                $order->update(['project_id' => $project->id]);
            }
        }

        if ($dryRun) {
            $this->comment("Dry-run : rien n'a ete modifie. Relance sans --dry-run pour appliquer.");
        }

        return self::SUCCESS;
    }
}
