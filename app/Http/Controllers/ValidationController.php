<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectValidationRequest;
use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Models\ValidationLog;
use App\Notifications\OrderApprovedAtLevelNotification;
use App\Notifications\OrderFinallyApprovedNotification;
use App\Notifications\OrderRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ValidationController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        // L'admin voit toutes les commandes en attente
        // Le validateur voit celles à son niveau
        $query = PurchaseOrder::where('status', 'pending')
            ->with(['user', 'attachments'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            abort_unless($user->validationLevel, 403);
            $query->where('current_level_order', $user->validationLevel->order);
        }

        $orders = $query->paginate(10);

        return Inertia::render('Validations/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizeValidation($purchaseOrder);

        $purchaseOrder->load([
            'user',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('Validations/Show', [
            'order'  => $purchaseOrder,
            'levels' => $levels,
        ]);
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeValidation($purchaseOrder);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $purchaseOrder->current_level_order)->firstOrFail();

        DB::transaction(function () use ($purchaseOrder, $user, $currentLevel) {
            ValidationLog::create([
                'purchase_order_id'   => $purchaseOrder->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'approved',
            ]);

            $nextLevel = ValidationLevel::nextAfter($currentLevel->order);

            if ($nextLevel) {
                $purchaseOrder->update([
                    'current_level_order' => $nextLevel->order,
                ]);

                // Notifier les validateurs du prochain niveau
                foreach ($nextLevel->validators as $validator) {
                    $validator->notify(new OrderApprovedAtLevelNotification($purchaseOrder, $currentLevel, $nextLevel));
                }
            } else {
                $purchaseOrder->update([
                    'status'              => 'approved',
                    'current_level_order' => null,
                ]);

                $purchaseOrder->user->notify(new OrderFinallyApprovedNotification($purchaseOrder));
            }
        });

        return redirect()->route('validations.index')
            ->with('success', 'Commande approuvée avec succès.');
    }

    public function reject(RejectValidationRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeValidation($purchaseOrder);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $purchaseOrder->current_level_order)->firstOrFail();

        DB::transaction(function () use ($request, $purchaseOrder, $user, $currentLevel) {
            ValidationLog::create([
                'purchase_order_id'   => $purchaseOrder->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'rejected',
                'comment'             => $request->comment,
            ]);

            $purchaseOrder->update([
                'status'              => 'rejected',
                'current_level_order' => null,
            ]);

            $purchaseOrder->user->notify(new OrderRejectedNotification($purchaseOrder, $currentLevel, $request->comment));
        });

        return redirect()->route('validations.index')
            ->with('success', 'Commande refusée.');
    }

    private function authorizeValidation(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isPending(), 403, 'Cette commande n\'est plus en attente de validation.');

        if ($user->isAdmin()) {
            return;
        }

        abort_unless($user->isValidateur() && $user->validationLevel, 403);
        abort_unless($user->validationLevel->order === $purchaseOrder->current_level_order, 403, 'Cette commande n\'est pas à votre niveau de validation.');
    }
}
