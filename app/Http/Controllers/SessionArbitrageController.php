<?php

namespace App\Http\Controllers;

use App\Models\ComiteArbitrage;
use App\Models\DemandeAutorisationPaiement;
use App\Models\SessionArbitrage;
use App\Models\SessionArbitrageDap;
use App\Models\VoteArbitrage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionArbitrageController extends Controller
{
    public function index(): Response
    {
        $user    = auth()->user();
        $isAdmin = $user->role?->slug === 'admin';

        $query = SessionArbitrage::with(['comite.entreprise', 'createdBy'])
            ->withCount('sessionDaps')
            ->latest();

        if (! $isAdmin) {
            $query->whereHas('comite.membres', fn ($q) => $q->where('user_id', $user->id)->where('is_active', true));
        }

        return Inertia::render('Arbitrage/Index', [
            'sessions' => $query->get(),
            'isAdmin'  => $isAdmin,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Arbitrage/Create', [
            'comites' => ComiteArbitrage::with('membres.user')->where('is_active', true)->get(),
            'daps'    => DemandeAutorisationPaiement::with(['expressionBesoin.user', 'expressionBesoin.entreprise'])
                ->where('statut', DemandeAutorisationPaiement::STATUT_VALIDEE)
                ->whereDoesntHave('paiement')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'comite_arbitrage_id'    => 'required|exists:comites_arbitrage,id',
            'titre'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'tresorerie_disponible'  => 'nullable|numeric|min:0',
            'bloquer_depassement'    => 'boolean',
            'dap_ids'                => 'required|array|min:1',
            'dap_ids.*'              => 'exists:demandes_autorisation_paiement,id',
        ]);

        $session = SessionArbitrage::create([
            'reference'              => SessionArbitrage::genererReference(),
            'comite_arbitrage_id'    => $validated['comite_arbitrage_id'],
            'titre'                  => $validated['titre'],
            'description'            => $validated['description'] ?? null,
            'tresorerie_disponible'  => $validated['tresorerie_disponible'] ?? null,
            'bloquer_depassement'    => $validated['bloquer_depassement'] ?? true,
            'statut'                 => SessionArbitrage::STATUT_BROUILLON,
            'created_by'             => auth()->id(),
        ]);

        foreach ($validated['dap_ids'] as $dapId) {
            SessionArbitrageDap::create([
                'session_arbitrage_id' => $session->id,
                'dap_id'               => $dapId,
            ]);
        }

        return redirect()->route('arbitrage.sessions.show', $session->id)
            ->with('success', 'Session d\'arbitrage créée.');
    }

    public function show(SessionArbitrage $session): Response
    {
        $user    = auth()->user();
        $isAdmin = $user->role?->slug === 'admin';

        $session->load([
            'comite.membres.user',
            'createdBy',
            'finaliseePar',
            'sessionDaps.dap.expressionBesoin.user',
            'sessionDaps.dap.expressionBesoin.entreprise',
            'votes.user',
        ]);

        $monVote = $this->getMonVote($session, $user->id);
        $votantsIds = $session->votes()->distinct('user_id')->pluck('user_id');

        return Inertia::render('Arbitrage/Show', [
            'session'         => $session,
            'isAdmin'         => $isAdmin,
            'monVote'         => $monVote,
            'votantsIds'      => $votantsIds,
            'quorumAtteint'   => $session->quorumAtteint(),
            'nbVotants'       => $session->nb_votants,
            'quorumRequis'    => $session->quorum_requis,
            'nbMembres'       => $session->nb_membres_actifs,
        ]);
    }

    public function ouvrirVote(SessionArbitrage $session): RedirectResponse
    {
        if ($session->statut !== SessionArbitrage::STATUT_BROUILLON) {
            return back()->with('error', 'La session n\'est pas en brouillon.');
        }

        if ($session->sessionDaps()->count() === 0) {
            return back()->with('error', 'Ajoutez au moins une DAP avant d\'ouvrir les votes.');
        }

        $session->update([
            'statut'         => SessionArbitrage::STATUT_EN_VOTE,
            'date_ouverture' => now()->toDateString(),
        ]);

        return back()->with('success', 'Votes ouverts. Les membres du comité peuvent maintenant voter.');
    }

    public function finaliser(Request $request, SessionArbitrage $session): RedirectResponse
    {
        if ($session->statut !== SessionArbitrage::STATUT_EN_VOTE) {
            return back()->with('error', 'La session doit être en phase de vote pour être finalisée.');
        }

        $session->calculerScores();
        $session->load('sessionDaps.dap.expressionBesoin');

        $itemsTries = $session->sessionDaps->sortBy(function ($item) {
            return [$item->score_moyen ?? 9999, $item->dap?->created_at];
        })->values();

        $tresorerie = $session->tresorerie_disponible;
        $bloquer    = $session->bloquer_depassement;
        $cumul      = 0;

        foreach ($itemsTries as $index => $item) {
            $montant = $item->dap?->montant ?? 0;
            $ordre   = $index + 1;

            if ($tresorerie !== null && $bloquer) {
                $cumul += $montant;
                $statut = $cumul <= $tresorerie ? 'selectionne' : 'reporte';
            } else {
                $statut = 'selectionne';
            }

            $item->update([
                'ordre_final' => $ordre,
                'statut'      => $statut,
            ]);
        }

        $session->update([
            'statut'       => SessionArbitrage::STATUT_CLOTUREE,
            'date_cloture' => now()->toDateString(),
            'finalise_par' => auth()->id(),
        ]);

        return redirect()->route('arbitrage.sessions.show', $session->id)
            ->with('success', 'Session finalisée. Ordre de priorité établi.');
    }

    public function destroy(SessionArbitrage $session): RedirectResponse
    {
        if ($session->statut !== SessionArbitrage::STATUT_BROUILLON) {
            return back()->with('error', 'Seules les sessions en brouillon peuvent être supprimées.');
        }

        $session->delete();
        return redirect()->route('arbitrage.sessions.index')->with('success', 'Session supprimée.');
    }

    private function getMonVote(SessionArbitrage $session, int $userId): array
    {
        $votes = VoteArbitrage::where('session_arbitrage_id', $session->id)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('dap_id');

        return $votes->map(fn ($v) => [
            'rang'        => $v->rang,
            'commentaire' => $v->commentaire,
        ])->toArray();
    }
}
