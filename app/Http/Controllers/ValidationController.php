<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectValidationRequest;
use App\Models\Boutique;
use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Models\ValidationLog;
use App\Notifications\OrderApprovedAtLevelNotification;
use App\Notifications\OrderFinallyApprovedNotification;
use App\Notifications\OrderRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ValidationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // L'admin voit toutes les commandes en attente
        // Le validateur voit celles à son niveau
        $query = PurchaseOrder::where('status', 'pending')
            ->with(['user', 'boutique', 'attachments'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            $validatableOrders = $user->validatableLevelOrders();
            abort_unless(count($validatableOrders) > 0, 403, 'Vous n\'avez aucun niveau de validation actif.');
            $query->whereIn('current_level_order', $validatableOrders);
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        $orders = $query->paginate(10)->withQueryString();

        return Inertia::render('Validations/Index', [
            'orders' => $orders,
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
            'levelsCount' => ValidationLevel::count(),
            'filters' => [
                'boutique_id' => $request->string('boutique_id')->toString(),
            ],
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizeValidation($purchaseOrder);

        $purchaseOrder->load([
            'user',
            'boutique',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
            'comments.user',
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
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->order),
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
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->order),
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

        $validatableOrders = $user->validatableLevelOrders();
        abort_unless(count($validatableOrders) > 0, 403, 'Vous n\'avez aucun niveau de validation actif.');
        abort_unless(
            in_array($purchaseOrder->current_level_order, $validatableOrders),
            403,
            'Cette commande n\'est pas à votre niveau de validation.'
        );
    }
}
