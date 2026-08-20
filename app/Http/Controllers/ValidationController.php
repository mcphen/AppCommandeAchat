<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectValidationRequest;
use App\Models\Boutique;
use App\Models\Circuit;
use App\Models\PurchaseOrder;
use App\Models\User;
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
        return $this->renderIndex($request, []);
    }

    /**
     * Meme page que index(), mais figee sur le circuit "prestation" (section separee
     * dans le menu, pour les validateurs des commandes de prestation de service).
     */
    public function prestations(Request $request): Response
    {
        $circuitId = Circuit::where('code', 'prestation')->value('id');
        $request->merge(['circuit_id' => $circuitId]);

        return $this->renderIndex($request, [
            'listRouteName'     => 'prestations.validations.index',
            'breadcrumbLabel'   => 'Validations Prestations',
            'breadcrumbHref'    => '/prestations/validations',
            'pageTitle'         => 'Validations — Prestations',
            'hideCircuitFilter' => true,
        ], $circuitId);
    }

    private function renderIndex(Request $request, array $sectionProps, ?int $circuitId = null): Response
    {
        $user = $request->user();

        // L'admin voit toutes les commandes en attente
        // Le validateur voit celles à son niveau
        $query = PurchaseOrder::where('status', 'pending')
            ->with(['user', 'boutique', 'circuit', 'attachments'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            $levels = $user->validatableLevels();
            abort_unless($levels->isNotEmpty(), 403, 'Vous n\'avez aucun niveau de validation actif.');
            $query->where(function ($q) use ($levels) {
                foreach ($levels as $level) {
                    $q->orWhere(fn ($qq) => $qq->where('circuit_id', $level->circuit_id)->where('current_level_order', $level->order));
                }
            });
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('circuit_id')) {
            $query->where('circuit_id', $request->integer('circuit_id'));
        }

        $orders = $query->paginate(10)->withQueryString();

        return Inertia::render('Validations/Index', array_merge([
            'orders' => $orders,
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
            'circuits' => Circuit::orderBy('name')->get(),
            'levelsCount' => $circuitId ? ValidationLevel::where('circuit_id', $circuitId)->count() : ValidationLevel::count(),
            'filters' => [
                'boutique_id' => $request->string('boutique_id')->toString(),
                'circuit_id'  => $request->string('circuit_id')->toString(),
            ],
        ], $sectionProps));
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizeValidation($purchaseOrder);

        $purchaseOrder->load([
            'user',
            'boutique',
            'fournisseur',
            'attachments',
            'lines.article.category',
            'lines.fournisseur',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
            'comments.user',
        ]);

        $levels = ValidationLevel::where('circuit_id', $purchaseOrder->circuit_id)->orderBy('order')->get();

        return Inertia::render('Validations/Show', [
            'order'  => $purchaseOrder,
            'levels' => $levels,
        ]);
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeValidation($purchaseOrder);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('circuit_id', $purchaseOrder->circuit_id)
            ->where('order', $purchaseOrder->current_level_order)
            ->firstOrFail();

        DB::transaction(function () use ($purchaseOrder, $user, $currentLevel) {
            ValidationLog::create([
                'purchase_order_id'   => $purchaseOrder->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'approved',
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->id),
            ]);

            $nextLevel = ValidationLevel::nextAfter($currentLevel->order, $currentLevel->circuit_id);

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

                $this->notifyAdmins($purchaseOrder, new OrderFinallyApprovedNotification($purchaseOrder));
            }
        });

        return redirect()->route('validations.index')
            ->with('success', 'Commande approuvée avec succès.');
    }

    public function reject(RejectValidationRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeValidation($purchaseOrder);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('circuit_id', $purchaseOrder->circuit_id)
            ->where('order', $purchaseOrder->current_level_order)
            ->firstOrFail();

        DB::transaction(function () use ($request, $purchaseOrder, $user, $currentLevel) {
            ValidationLog::create([
                'purchase_order_id'   => $purchaseOrder->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'rejected',
                'comment'             => $request->comment,
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->id),
            ]);

            $purchaseOrder->update([
                'status'              => 'rejected',
                'current_level_order' => null,
            ]);

            $this->notifyAdmins($purchaseOrder, new OrderRejectedNotification($purchaseOrder, $currentLevel, $request->comment));
        });

        return redirect()->route('validations.index')
            ->with('success', 'Commande refusée.');
    }

    private function notifyAdmins(PurchaseOrder $purchaseOrder, $notification): void
    {
        // Les commandes proviennent désormais de Sage100 (aucun demandeur humain propriétaire) :
        // on notifie les admins plutôt que $purchaseOrder->user (compte système).
        User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->get()
            ->each(fn ($admin) => $admin->notify($notification));
    }

    private function authorizeValidation(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isPending(), 403, 'Cette commande n\'est plus en attente de validation.');

        if ($user->isAdmin()) {
            return;
        }

        $levels = $user->validatableLevels();
        abort_unless($levels->isNotEmpty(), 403, 'Vous n\'avez aucun niveau de validation actif.');
        abort_unless(
            $levels->contains(fn ($level) => $level->circuit_id === $purchaseOrder->circuit_id && $level->order === $purchaseOrder->current_level_order),
            403,
            'Cette commande n\'est pas à votre niveau de validation.'
        );
    }
}
