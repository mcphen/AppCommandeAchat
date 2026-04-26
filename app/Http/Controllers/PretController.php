<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Boutique;
use App\Models\ModeReglement;
use App\Models\Pret;
use App\Models\ValidationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PretController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Pret::with(['agent.boutique', 'modeReglement'])
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->whereHas('agent', fn ($a) => $a->where('boutique_id', $user->boutique_id)))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')->toString()))
            ->when($request->filled('boutique_id'), fn ($q) => $q->whereHas('agent', fn ($a) => $a->where('boutique_id', $request->integer('boutique_id'))))
            ->latest();

        return Inertia::render('Caisse/Prets/Index', [
            'prets'     => $query->paginate(15)->withQueryString(),
            'boutiques' => $user->isAdmin() ? Boutique::where('is_active', true)->orderBy('name')->get() : [],
            'filters'   => [
                'statut'      => $request->string('statut')->toString(),
                'boutique_id' => $request->string('boutique_id')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $agentId = $request->integer('agent_id');
        $agent   = $agentId ? Agent::with('compte')->findOrFail($agentId) : null;

        $user = $request->user();
        if ($user->isCaissier() && $user->boutique_id && $agent) {
            abort_unless($agent->boutique_id === $user->boutique_id, 403);
        }

        $agents = Agent::where('is_active', true)
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->where('boutique_id', $user->boutique_id))
            ->orderBy('nom')->orderBy('prenom')
            ->with('compte')
            ->get();

        return Inertia::render('Caisse/Prets/Form', [
            'agent'  => $agent,
            'agents' => $agents,
            'modes'  => ModeReglement::actifs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'agent_id'       => ['required', 'exists:agents,id'],
            'montant_demande' => ['required', 'numeric', 'min:1'],
            'motif'          => ['nullable', 'string', 'max:500'],
        ]);

        $agent  = Agent::with('compte')->findOrFail($validated['agent_id']);
        $compte = $agent->compte;

        abort_unless($compte && $compte->is_active, 422, 'Compte épargne inactif.');
        abort_unless(! $agent->pretActif(), 422, 'Cet agent a déjà un prêt en cours.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($agent->boutique_id === $user->boutique_id, 403);
        }

        $pret = Pret::create([
            ...$validated,
            'compte_epargne_id' => $compte->id,
            'statut'            => 'draft',
            'recorded_by'       => $user->id,
        ]);

        return redirect()->route('caisse.prets.show', $pret)
            ->with('success', 'Demande de prêt créée.');
    }

    public function show(Pret $pret): Response
    {
        $user = auth()->user();
        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($pret->agent->boutique_id === $user->boutique_id, 403);
        }

        $pret->load(['agent.boutique', 'modeReglement', 'remboursements.modeReglement', 'remboursements.recorder', 'validationLogs.validationLevel', 'validationLogs.user']);

        return Inertia::render('Caisse/Prets/Show', [
            'pret'   => $pret,
            'modes'  => ModeReglement::actifs(),
            'levels' => ValidationLevel::orderBy('order')->get(),
        ]);
    }

    public function submit(Pret $pret): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($pret->canBeSubmitted(), 403, 'Ce prêt ne peut pas être soumis.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($pret->agent->boutique_id === $user->boutique_id, 403);
        }

        $firstLevel = ValidationLevel::orderBy('order')->first();
        abort_unless($firstLevel, 422, 'Aucun niveau de validation configuré.');

        $pret->update([
            'statut'              => 'pending',
            'current_level_order' => $firstLevel->order,
            'submitted_at'        => now(),
        ]);

        return redirect()->route('caisse.prets.show', $pret)
            ->with('success', 'Demande soumise au circuit de validation.');
    }

    public function decaisser(Request $request, Pret $pret): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($pret->canBeDecaisse(), 403, 'Ce prêt ne peut pas être décaissé.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($pret->agent->boutique_id === $user->boutique_id, 403);
        }

        $validated = $request->validate([
            'montant_accorde'   => ['required', 'numeric', 'min:1'],
            'mode_reglement_id' => ['required', 'exists:modes_reglement,id'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $pret) {
            $pret->update([
                'statut'            => 'decaisse',
                'montant_accorde'   => $validated['montant_accorde'],
                'mode_reglement_id' => $validated['mode_reglement_id'],
                'decaisse_at'       => now(),
            ]);

            // Bloquer l'épargne comme garantie
            $pret->compteEpargne->increment('solde_bloque', $validated['montant_accorde']);
        });

        return redirect()->route('caisse.prets.show', $pret)
            ->with('success', 'Prêt décaissé avec succès.');
    }
}
