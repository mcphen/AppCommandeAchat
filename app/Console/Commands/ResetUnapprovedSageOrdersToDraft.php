<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Notifications\OrderAwaitingSubmissionNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetUnapprovedSageOrdersToDraft extends Command
{
    protected $signature = 'purchase-orders:reset-to-draft {--dry-run : Afficher ce qui serait modifie sans rien changer}';

    protected $description = "Remet en brouillon les commandes importees de Sage100 qui n'ont jamais ete reellement approuvees par un validateur, suite au passage a la soumission manuelle obligatoire (avec piece jointe).";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $orders = PurchaseOrder::query()
            ->where('source', 'sage')
            ->whereIn('status', ['pending', 'needs_revision', 'rejected'])
            ->whereDoesntHave('validationLogs', fn ($q) => $q->where('action', 'approved'))
            ->with('user')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande a corriger.');

            return self::SUCCESS;
        }

        $this->info("{$orders->count()} commande(s) a remettre en brouillon (jamais approuvees) :");

        foreach ($orders as $order) {
            $this->line(" - {$order->sage_reference} (#{$order->id}) : {$order->status} -> draft");

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($order) {
                $order->update([
                    'status'              => 'draft',
                    'current_level_order' => null,
                    'submitted_at'        => null,
                ]);

                // Ne notifier que s'il s'agit d'un vrai demandeur (pas le compte systeme
                // Sage100 utilise quand aucun collaborateur n'a ete identifie).
                if ($order->user && $order->user->role_id !== null) {
                    $order->user->notify(new OrderAwaitingSubmissionNotification($order));
                }
            });
        }

        if ($dryRun) {
            $this->comment("Dry-run : rien n'a ete modifie. Relance sans --dry-run pour appliquer.");
        }

        return self::SUCCESS;
    }
}
