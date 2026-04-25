<?php

namespace App\Http\Controllers;

use App\Exports\DapExport;
use App\Models\DemandeAutorisationPaiement;
use App\Models\ExpressionBesoin;
use App\Models\NiveauValidation;
use App\Models\SeuilValidation;
use App\Models\ValidationDap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Inertia\Response;

class ValidationDapController extends Controller
{
    public function index(): Response
    {
        $user   = auth()->user();
        $niveau = $user->niveauValidation;

        $daps = DemandeAutorisationPaiement::with([
                'expressionBesoin.user',
                'expressionBesoin.entreprise',
                'budgetAnnuel',
                'validations.niveauValidation',
            ])
            ->where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)
            ->when($niveau, function ($q) use ($niveau) {
                $seuil = SeuilValidation::where('niveau_validation_id', $niveau->id)->value('montant_seuil') ?? 0;
                $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('montant', '>=', $seuil))
                  ->whereDoesntHave('validations', fn ($sq) => $sq->where('niveau_validation_id', $niveau->id));
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('ValidationsDAP/Index', [
            'daps'   => $daps,
            'niveau' => $niveau,
            'mode'   => 'pending',
        ]);
    }

    public function toutes(Request $request): Response
    {
        $user   = auth()->user();
        $niveau = $user->niveauValidation;

        $filters = [
            'search'       => $request->string('search')->toString(),
            'statut'       => $request->string('statut')->toString(),
            'user_id'      => $request->string('user_id')->toString(),
            'entreprise_id' => $request->string('entreprise_id')->toString(),
        ];

        $daps = DemandeAutorisationPaiement::with([
                'expressionBesoin.user',
                'expressionBesoin.entreprise',
                'budgetAnnuel',
                'validations.niveauValidation',
            ])
            ->when($filters['search'], function ($q) use ($filters) {
                $s = $filters['search'];
                $q->where('reference', 'like', "%{$s}%")
                  ->orWhereHas('expressionBesoin', fn ($sq) =>
                      $sq->where('objet', 'like', "%{$s}%")
                         ->orWhere('beneficiaire', 'like', "%{$s}%")
                  );
            })
            ->when($filters['statut'], fn ($q) => $q->where('statut', $filters['statut']))
            ->when($filters['user_id'], fn ($q) => $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('user_id', $filters['user_id'])))
            ->when($filters['entreprise_id'], fn ($q) => $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $filters['entreprise_id'])))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('ValidationsDAP/Index', [
            'daps'        => $daps,
            'niveau'      => $niveau,
            'mode'        => 'all',
            'filters'     => $filters,
            'demandeurs'  => \App\Models\User::whereHas('expressionsBesoin')->orderBy('name')->get(['id', 'name']),
            'entreprises' => \App\Models\Entreprise::orderBy('nom')->get(['id', 'nom']),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $filters = [
            'search'        => $request->string('search')->toString(),
            'statut'        => $request->string('statut')->toString(),
            'user_id'       => $request->string('user_id')->toString(),
            'entreprise_id' => $request->string('entreprise_id')->toString(),
        ];

        return Excel::download(new DapExport($filters), 'dap-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function show(DemandeAutorisationPaiement $dap): Response
    {
        $user = auth()->user();

        // Un employé ne peut consulter que la DAP issue de sa propre EB
        if ($user->isEmploye() && $dap->expressionBesoin->user_id !== $user->id) {
            abort(403);
        }

        $dap->load([
            'expressionBesoin.user',
            'expressionBesoin.entreprise',
            'expressionBesoin.attachments',
            'budgetAnnuel',
            'validations.niveauValidation',
            'validations.validateur',
            'createdBy',
        ]);

        return Inertia::render('ValidationsDAP/Show', [
            'dap' => $dap,
        ]);
    }

    public function approuver(Request $request, DemandeAutorisationPaiement $dap): RedirectResponse
    {
        $validated = $request->validate([
            'commentaire' => 'nullable|string',
        ]);

        $user   = auth()->user();
        $niveau = $user->niveauValidation;

        ValidationDap::create([
            'dap_id'               => $dap->id,
            'niveau_validation_id' => $niveau->id,
            'validateur_id'        => $user->id,
            'statut'               => ValidationDap::STATUT_APPROUVE,
            'commentaire'          => $validated['commentaire'] ?? null,
            'validated_at'         => now(),
        ]);

        // Vérifier si tous les niveaux requis ont validé
        if ($this->tousLesNiveauxValides($dap)) {
            $dap->update(['statut' => DemandeAutorisationPaiement::STATUT_VALIDEE]);
        }

        return redirect()->route('validations-dap.index')
            ->with('success', 'DAP approuvée avec succès.');
    }

    public function rejeter(Request $request, DemandeAutorisationPaiement $dap): RedirectResponse
    {
        $validated = $request->validate([
            'commentaire' => 'required|string',
        ]);

        $user   = auth()->user();
        $niveau = $user->niveauValidation;

        ValidationDap::create([
            'dap_id'               => $dap->id,
            'niveau_validation_id' => $niveau->id,
            'validateur_id'        => $user->id,
            'statut'               => ValidationDap::STATUT_REJETE,
            'commentaire'          => $validated['commentaire'],
            'validated_at'         => now(),
        ]);

        $dap->update(['statut' => DemandeAutorisationPaiement::STATUT_REJETEE]);

        // Remettre l'EB en attente pour permettre à l'employé de la corriger
        $dap->expressionBesoin->update([
            'statut'      => ExpressionBesoin::STATUT_REJETEE,
            'motif_rejet' => $validated['commentaire'],
            'rejected_at' => now(),
        ]);

        return redirect()->route('validations-dap.index')
            ->with('success', 'DAP rejetée. L\'employé a été notifié.');
    }

    private function tousLesNiveauxValides(DemandeAutorisationPaiement $dap): bool
    {
        $montant = $dap->expressionBesoin->montant;

        // Niveaux requis selon le montant (sauf compta qui est déjà fait)
        $niveauxRequis = NiveauValidation::whereHas('seuil', fn ($q) => $q->where('montant_seuil', '<=', $montant))
            ->where('slug', '!=', 'compta')
            ->pluck('id');

        $niveauxValides = $dap->validations()
            ->where('statut', ValidationDap::STATUT_APPROUVE)
            ->whereIn('niveau_validation_id', $niveauxRequis)
            ->pluck('niveau_validation_id');

        return $niveauxRequis->diff($niveauxValides)->isEmpty();
    }
}
