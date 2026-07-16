<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Notifications\OrderSubmittedNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RestartPurchaseOrderValidationService
{
    public function restart(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $firstLevel = ValidationLevel::first_level();

        if (! $firstLevel) {
            throw new RuntimeException('Aucun niveau de validation n\'est configuré.');
        }

        $order = DB::transaction(function () use ($purchaseOrder, $firstLevel) {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchaseOrder->id);

            if ($order->status !== 'approved') {
                throw new RuntimeException('Seule une commande approuvée peut être relancée.');
            }

            if ($order->delivery_status !== null) {
                throw new RuntimeException('Une commande déjà confirmée ou réceptionnée ne peut pas être relancée.');
            }

            $order->validationLogs()->delete();
            $order->update([
                'status'              => 'pending',
                'current_level_order' => $firstLevel->order,
                'submitted_at'        => now(),
            ]);

            return $order->fresh();
        });

        $firstLevel->validators()
            ->get()
            ->each(fn ($validator) => $validator->notify(new OrderSubmittedNotification($order, $firstLevel)));

        return $order;
    }
}
