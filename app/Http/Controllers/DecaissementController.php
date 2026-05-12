<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\Decaissement;
use App\Models\ModeReglement;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\DecaissementEnregistreNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DecaissementController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = PurchaseOrder::where('status', 'approved')
            ->with(['boutique', 'fournisseur', 'decaissements'])
            ->withCount('decaissements')
            ->latest('submitted_at');

        if ($user->isCaissier() && $user->boutique_id) {
            $query->where('boutique_id', $user->boutique_id);
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('payment_status')) {
            $status = $request->string('payment_status')->toString();
            if ($status === 'unpaid') {
                $query->whereNull('payment_status');
            } else {
                $query->where('payment_status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        $scopedBase = PurchaseOrder::where('status', 'approved')
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->where('boutique_id', $user->boutique_id));

        $stats = [
            'unpaid'         => (clone $scopedBase)->whereNull('payment_status')->count(),
            'partially_paid' => (clone $scopedBase)->where('payment_status', 'partially_paid')->count(),
            'paid'           => (clone $scopedBase)->where('payment_status', 'paid')->count(),
        ];

        return Inertia::render('Decaissements/Index', [
            'orders'    => $orders,
            'stats'     => $stats,
            'boutiques' => $user->isAdmin() ? Boutique::where('is_active', true)->orderBy('name')->get() : [],
            'filters'   => [
                'boutique_id'    => $request->string('boutique_id')->toString(),
                'payment_status' => $request->string('payment_status')->toString(),
                'search'         => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isApproved(), 403, 'Cette commande n\'est pas approuvée.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($purchaseOrder->boutique_id === $user->boutique_id, 403, 'Accès non autorisé.');
        }

        $purchaseOrder->load(['boutique', 'fournisseur', 'user', 'lines.article', 'decaissements.recorder', 'decaissements.modeReglement']);

        return Inertia::render('Decaissements/Show', [
            'order'  => $purchaseOrder,
            'modes'  => ModeReglement::actifs(),
        ]);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isApproved(), 403, 'Cette commande n\'est pas approuvée.');
        abort_unless($purchaseOrder->canBeDecaisse(), 403, 'Cette commande est déjà entièrement décaissée.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($purchaseOrder->boutique_id === $user->boutique_id, 403, 'Accès non autorisé.');
        }

        $resteADecaisser = $purchaseOrder->resteADecaisser();

        $validated = $request->validate([
            'montant'           => ['required', 'numeric', 'min:1', "max:{$resteADecaisser}"],
            'mode_reglement_id' => ['required', 'exists:modes_reglement,id'],
            'reference'         => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'decaissement_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder, $user) {
            $decaissement = Decaissement::create([
                ...$validated,
                'purchase_order_id' => $purchaseOrder->id,
                'recorded_by'       => $user->id,
            ]);

            $totalDecaisse = (float) $purchaseOrder->decaissements()->sum('montant');
            $paymentStatus = $totalDecaisse >= (float) $purchaseOrder->amount ? 'paid' : 'partially_paid';

            $purchaseOrder->update(['payment_status' => $paymentStatus]);

            // Notifier le dernier validateur
            $dernierValidateur = $purchaseOrder->validationLogs()
                ->where('action', 'approved')
                ->latest()
                ->first()
                ?->user;

            if ($dernierValidateur) {
                $dernierValidateur->notify(new DecaissementEnregistreNotification($purchaseOrder, $decaissement));
            }

            // Notifier les admins (sauf si l'admin est lui-même le dernier validateur)
            User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                ->where('id', '!=', $dernierValidateur?->id)
                ->each(fn ($admin) => $admin->notify(new DecaissementEnregistreNotification($purchaseOrder, $decaissement)));
        });

        return redirect()->route('decaissements.show', $purchaseOrder)
            ->with('success', 'Décaissement enregistré avec succès.');
    }
}
