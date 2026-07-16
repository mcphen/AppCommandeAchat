<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Notifications\OrderSubmittedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RestartPurchaseOrderValidationService
{
    public function restart(PurchaseOrder $purchaseOrder, bool $force = false): PurchaseOrder
    {
        $firstLevel = ValidationLevel::first_level();

        if (! $firstLevel) {
            throw new RuntimeException('Aucun niveau de validation n\'est configuré.');
        }

        $order = DB::transaction(function () use ($purchaseOrder, $firstLevel, $force) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);

            if ($order->status !== 'approved') {
                throw new RuntimeException('Seule une commande approuvée peut être relancée.');
            }

            if (! $force && in_array($order->delivery_status, ['partially_received', 'received'], true)) {
                throw new RuntimeException('Une commande déjà réceptionnée, même partiellement, ne peut pas être relancée.');
            }

            $order->validationLogs()->delete();
            $order->update([
                'status'              => 'pending',
                'current_level_order' => $firstLevel->order,
                'submitted_at'        => now(),
                'order_number'        => null,
                'delivery_status'     => null,
                'ordered_at'          => null,
                'fully_received_at'   => null,
            ]);

            return $order->fresh();
        });

        $firstLevel->validators()
            ->get()
            ->each(fn ($validator) => $validator->notify(new OrderSubmittedNotification($order, $firstLevel)));

        return $order;
    }
}
