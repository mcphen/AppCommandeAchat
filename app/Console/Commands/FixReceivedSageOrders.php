<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class FixReceivedSageOrders extends Command
{
    /**
     * Corrige les commandes Sage deja entierement receptionnees mais restees
     * "en attente" (survenu avant que le webhook applique cette regle
     * automatiquement). Idempotent : peut etre relancee sans risque.
     */
    protected $signature = 'sage:fix-received-orders {--dry-run : Affiche ce qui serait corrige sans rien modifier}';

    protected $description = 'Approuve automatiquement les commandes Sage deja entierement receptionnees mais restees en attente';

    public function handle(): int
    {
        $orders = PurchaseOrder::where('source', 'sage')
            ->where('status', 'pending')
            ->where('delivery_status', 'received')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande a corriger.');

            return self::SUCCESS;
        }

        $this->info("{$orders->count()} commande(s) trouvee(s) : reçue entièrement mais encore en attente.");

        foreach ($orders as $order) {
            $this->line(" - {$order->sage_reference} ({$order->title})");

            if (! $this->option('dry-run')) {
                $order->update([
                    'status'              => 'approved',
                    'current_level_order' => null,
                ]);
            }
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry-run : rien n\'a ete modifie. Relance sans --dry-run pour appliquer.');
        } else {
            $this->info('Corrige.');
        }

        return self::SUCCESS;
    }
}
