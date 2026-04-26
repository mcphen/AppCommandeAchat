<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Boutique;
use App\Models\CompteEpargne;
use App\Models\ModeReglement;
use App\Models\Pret;
use App\Models\TransactionEpargne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CaisseController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $agentQuery = Agent::with(['boutique', 'compte'])
            ->where('is_active', true)
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->where('boutique_id', $user->boutique_id))
            ->when($request->filled('boutique_id'), fn ($q) => $q->where('boutique_id', $request->integer('boutique_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search')->toString();
                $q->where(fn ($q2) => $q2->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('matricule', 'like', "%{$s}%"));
            })
            ->latest();

        $scopeBase = Agent::where('is_active', true)
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->where('boutique_id', $user->boutique_id));

        $agentIds = (clone $scopeBase)->pluck('id');

        $stats = [
            'total_agents'     => (clone $scopeBase)->count(),
            'total_epargne'    => (int) CompteEpargne::whereIn('agent_id', $agentIds)->sum('solde_epargne'),
            'prets_en_cours'   => Pret::whereIn('agent_id', $agentIds)->where('statut', 'decaisse')->count(),
            'encours_prets'    => (int) Pret::whereIn('agent_id', $agentIds)->where('statut', 'decaisse')->sum('montant_accorde'),
            'transactions_jour' => TransactionEpargne::whereHas('compteEpargne', fn ($q) => $q->whereIn('agent_id', $agentIds))
                ->whereDate('transaction_date', today())->count(),
        ];

        return Inertia::render('Caisse/Index', [
            'agents'    => $agentQuery->paginate(20)->withQueryString(),
            'stats'     => $stats,
            'boutiques' => $user->isAdmin() ? Boutique::where('is_active', true)->orderBy('name')->get() : [],
            'filters'   => [
                'boutique_id' => $request->string('boutique_id')->toString(),
                'search'      => $request->string('search')->toString(),
            ],
        ]);
    }

    public function showAgent(Agent $agent, Request $request): Response
    {
        $user = $request->user();

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($agent->boutique_id === $user->boutique_id, 403);
        }

        $agent->load(['boutique', 'user']);
        $compte = $agent->compte()->with(['transactions.modeReglement', 'transactions.recorder'])->first();
        $prets  = $agent->prets()->with(['modeReglement', 'remboursements.modeReglement', 'validationLogs.validationLevel', 'validationLogs.user'])->get();

        return Inertia::render('Caisse/Comptes/Show', [
            'agent'  => $agent,
            'compte' => $compte,
            'prets'  => $prets,
            'modes'  => ModeReglement::actifs(),
        ]);
    }
}
