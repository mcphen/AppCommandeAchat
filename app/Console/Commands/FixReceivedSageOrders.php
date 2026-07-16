<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;

class FixReceivedSageOrders extends Command
{
    /**
     * Corrige deux effets de bord d'anciennes versions du webhook Sage, sur les
     * commandes deja importees avant le fix :
     *  1) `fully_received_at` fige sur la date d'import (now()) au lieu de la
     *     vraie date de livraison Sage (deja correcte sur la reception liee) ;
     *  2) `ordered_at` jamais renseigne, ce qui casse l'affichage/tri du tableau
     *     de bord Receptions ("Commandee le").
     *
     * Ne touche plus au statut de validation : le suivi de livraison/cloture est
     * purement informatif, une commande receptionnee ou cloturee cote Sage doit
     * quand meme repasser par le circuit de validation interne de l'app.
     * Idempotent : peut etre relancee sans risque.
     */
    protected $signature = 'sage:fix-received-orders {--dry-run : Affiche ce qui serait corrige sans rien modifier}';

    protected $description = 'Corrige les dates de commande/reception des commandes Sage deja importees';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->fixReceivedDate($dryRun);
        $this->fixOrderedDate($dryRun);

        if ($dryRun) {
            $this->comment("Dry-run : rien n'a ete modifie. Relance sans --dry-run pour appliquer.");
        }

        return self::SUCCESS;
    }

    private function fixOrderedDate(bool $dryRun): void
    {
        $orders = PurchaseOrder::where('source', 'sage')
            ->whereNull('ordered_at')
            ->whereNotNull('order_date')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Ordered_at : aucune commande a corriger.');

            return;
        }

        $this->info("Ordered_at : {$orders->count()} commande(s) sans date de commande renseignee.");

        foreach ($orders as $order) {
            $this->line(" - {$order->sage_reference} : ordered_at -> {$order->order_date}");

            if (! $dryRun) {
                $order->update(['ordered_at' => $order->order_date]);
            }
        }
    }

    private function fixReceivedDate(bool $dryRun): void
    {
        $orders = PurchaseOrder::where('source', 'sage')
            ->where('delivery_status', 'received')
            ->whereNotNull('fully_received_at')
            ->with(['receptions' => fn ($q) => $q->orderByDesc('received_at')])
            ->get();

        $toFix = $orders->filter(function ($order) {
            $realDate = $order->receptions->first()?->received_at;

            return $realDate && ! $realDate->equalTo($order->fully_received_at);
        });

        if ($toFix->isEmpty()) {
            $this->info('Dates : aucune commande a corriger.');

            return;
        }

        $this->info("Dates : {$toFix->count()} commande(s) avec une fully_received_at incorrecte (date d'import au lieu de la vraie date Sage).");

        foreach ($toFix as $order) {
            $realDate = $order->receptions->first()->received_at;
            $this->line(" - {$order->sage_reference} : {$order->fully_received_at} -> {$realDate}");

            if (! $dryRun) {
                $order->update(['fully_received_at' => $realDate]);
            }
        }
    }
}
