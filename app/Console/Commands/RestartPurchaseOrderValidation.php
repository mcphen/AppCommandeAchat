<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Services\RestartPurchaseOrderValidationService;
use Illuminate\Console\Command;
use RuntimeException;

class RestartPurchaseOrderValidation extends Command
{
    protected $signature = 'purchase-orders:restart-validation
                            {purchase_order : ID du bon de commande}
                            {--force : Relancer même si la commande est déjà réceptionnée (partiellement ou entièrement)}';

    protected $description = 'Réinitialise une commande approuvée et relance son circuit au premier niveau';

    public function handle(RestartPurchaseOrderValidationService $service): int
    {
        $id = $this->argument('purchase_order');

        if (! ctype_digit((string) $id) || (int) $id < 1) {
            $this->error('L\'ID du bon de commande doit être un entier positif.');

            return self::INVALID;
        }

        $order = PurchaseOrder::find($id);

        if (! $order) {
            $this->error("Aucun bon de commande trouvé avec l'ID {$id}.");

            return self::FAILURE;
        }

        try {
            $order = $service->restart($order, (bool) $this->option('force'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Bon de commande #{$order->id} réinitialisé.");
        $this->line("Statut : pending — niveau courant : {$order->current_level_order}");

        return self::SUCCESS;
    }
}
