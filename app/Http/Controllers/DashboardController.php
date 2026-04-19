<?php

namespace App\Http\Controllers;

use App\Exports\DfPendingDapsExport;
use App\Models\BudgetAnnuel;
use App\Models\DemandeAutorisationPaiement;
use App\Models\Entreprise;
use App\Models\ExpressionBesoin;
use App\Models\NiveauValidation;
use App\Models\Paiement;
use App\Models\SeuilValidation;
use App\Models\Validateur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->isCompta()) {
            return $this->comptaDashboard();
        }

        if ($user->isDf()) {
            return $this->dfDashboard($user);
        }

        if ($user->isValidateur()) {
            return $this->validateurDashboard($user);
        }

        return $this->employeDashboard($user);
    }

    private function adminDashboard(): Response
    {
        $annee = now()->year;
        $periode = request('periode', 'this_month');
        $entrepriseId = request('entreprise_id');
        $dateDebutInput = request('date_debut');
        $dateFinInput = request('date_fin');

        $dateFrom = match ($periode) {
            'last_30_days' => now()->subDays(30)->startOfDay(),
            'year_to_date' => now()->startOfYear(),
            'custom' => $dateDebutInput ? Carbon::parse($dateDebutInput)->startOfDay() : now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $dateTo = match ($periode) {
            'custom' => $dateFinInput ? Carbon::parse($dateFinInput)->endOfDay() : now()->endOfDay(),
            default => now()->endOfDay(),
        };

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $ebBase = ExpressionBesoin::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($entrepriseId, fn ($q) => $q->where('entreprise_id', $entrepriseId));

        $dapBase = DemandeAutorisationPaiement::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($entrepriseId, fn ($q) =>
                $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $entrepriseId))
            );

        $paiementBase = Paiement::query()
            ->whereBetween('date_paiement', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->when($entrepriseId, fn ($q) =>
                $q->whereHas('dap.expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $entrepriseId))
            );

        $ebTotal = (clone $ebBase)->count();
        $ebValidees = (clone $ebBase)->where('statut', ExpressionBesoin::STATUT_VALIDEE)->count();
        $dapPayees = (clone $dapBase)->where('statut', DemandeAutorisationPaiement::STATUT_PAYEE)->count();
        $tauxTransformation = $ebTotal > 0 ? round(($dapPayees / $ebTotal) * 100, 1) : 0;

        $ebRejetees30j = (clone $ebBase)
            ->where('statut', ExpressionBesoin::STATUT_REJETEE)
            ->count();

        $montantEngageMois = (clone $dapBase)
            ->with('expressionBesoin:id,montant')
            ->get()
            ->sum(fn ($dap) => (float) ($dap->expressionBesoin?->montant ?? 0));

        $montantPayeMois = (clone $paiementBase)->sum('montant');

        $dapsBloquees = (clone $dapBase)
            ->with(['expressionBesoin.user', 'expressionBesoin.entreprise'])
            ->where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)
            ->where('created_at', '<=', now()->subDays(3))
            ->orderBy('created_at')
            ->limit(6)
            ->get()
            ->map(function ($dap) {
                return [
                    'id' => $dap->id,
                    'reference' => $dap->reference,
                    'objet' => $dap->expressionBesoin?->objet,
                    'demandeur' => $dap->expressionBesoin?->user?->name,
                    'entreprise' => $dap->expressionBesoin?->entreprise?->nom,
                    'jours_retard' => (int) max(1, $dap->created_at->diffInDays(now())),
                ];
            });

        $budgetsAlerte = BudgetAnnuel::where('annee', $annee)
            ->where('montant_total', '>', 0)
            ->when($entrepriseId, fn ($q) => $q->where('entreprise_id', $entrepriseId))
            ->whereRaw('(montant_consomme / montant_total) >= 0.8')
            ->count();

        $budgetsSocietes = Entreprise::query()
            ->when($entrepriseId, fn ($q) => $q->where('id', $entrepriseId))
            ->with(['budgetsAnnuels' => fn ($q) => $q->where('annee', $annee)])
            ->get()
            ->map(function ($e) {
                $b = $e->budgetsAnnuels->first();

                return [
                    'id' => $e->id,
                    'nom' => $e->nom,
                    'code' => $e->code,
                    'budget_id' => $b?->id,
                    'montant_total' => (float) ($b?->montant_total ?? 0),
                    'montant_consomme' => (float) ($b?->montant_consomme ?? 0),
                    'montant_disponible' => (float) (($b?->montant_total ?? 0) - ($b?->montant_consomme ?? 0)),
                    'pourcentage' => $b && $b->montant_total > 0
                        ? round($b->montant_consomme / $b->montant_total * 100, 1)
                        : 0,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'eb_en_attente' => (clone $ebBase)->where('statut', ExpressionBesoin::STATUT_EN_ATTENTE)->count(),
                'dap_en_cours'  => (clone $dapBase)->where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)->count(),
                'dap_validees'  => (clone $dapBase)->where('statut', DemandeAutorisationPaiement::STATUT_VALIDEE)->count(),
                'dap_payees'    => (clone $dapBase)->where('statut', DemandeAutorisationPaiement::STATUT_PAYEE)->count(),
                'eb_rejetees_30j' => $ebRejetees30j,
                'montant_engage_mois' => (float) $montantEngageMois,
                'montant_paye_mois' => (float) $montantPayeMois,
                'taux_transformation' => $tauxTransformation,
                'daps_bloquees' => $dapsBloquees->count(),
                'budgets_alertes' => $budgetsAlerte,
            ],
            'recentDaps' => (clone $dapBase)->with(['expressionBesoin.user', 'expressionBesoin.entreprise'])
                ->latest()->limit(8)->get(),
            'recentEb' => (clone $ebBase)->with(['user', 'entreprise'])
                ->latest()->limit(8)->get(),
            'dapsBloquees' => $dapsBloquees,
            'budgetsSocietes' => $budgetsSocietes,
            'entreprises' => Entreprise::query()->select('id', 'nom')->orderBy('nom')->get(),
            'filters' => [
                'periode' => $periode,
                'entreprise_id' => $entrepriseId ? (string) $entrepriseId : '',
                'date_debut' => $dateDebutInput,
                'date_fin' => $dateFinInput,
            ],
        ]);
    }

    private function comptaDashboard(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'eb_en_attente' => ExpressionBesoin::where('statut', ExpressionBesoin::STATUT_EN_ATTENTE)->count(),
                'dap_en_cours'  => DemandeAutorisationPaiement::where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)->count(),
                'dap_validees'  => DemandeAutorisationPaiement::where('statut', DemandeAutorisationPaiement::STATUT_VALIDEE)->count(),
            ],
            'recentEb' => ExpressionBesoin::with(['user', 'entreprise'])
                ->where('statut', ExpressionBesoin::STATUT_EN_ATTENTE)
                ->latest()->limit(8)->get(),
        ]);
    }

    private function dfDashboard($user): Response
    {
        $annee       = now()->year;
        $niveauDf    = $user->niveauValidation;
        $validateur  = $user->validateur;
        $seuilDf     = SeuilValidation::where('niveau_validation_id', $niveauDf->id)->value('montant_seuil') ?? 0;
        $periode = request('periode', 'this_month');
        $entrepriseId = request('entreprise_id');
        $dateDebutInput = request('date_debut');
        $dateFinInput = request('date_fin');

        $dateFrom = match ($periode) {
            'last_30_days' => now()->subDays(30)->startOfDay(),
            'year_to_date' => now()->startOfYear(),
            'custom' => $dateDebutInput ? Carbon::parse($dateDebutInput)->startOfDay() : now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $dateTo = match ($periode) {
            'custom' => $dateFinInput ? Carbon::parse($dateFinInput)->endOfDay() : now()->endOfDay(),
            default => now()->endOfDay(),
        };

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        // Budget par société — année courante
        $societes = Entreprise::query()
            ->when($entrepriseId, fn ($q) => $q->where('id', $entrepriseId))
            ->with(['budgetsAnnuels' => fn ($q) => $q->where('annee', $annee)])
            ->get();
        $budgetsSocietes = $societes->map(function ($e) use ($annee) {
            $b = $e->budgetsAnnuels->first();
            return [
                'id'                => $e->id,
                'nom'               => $e->nom,
                'code'              => $e->code,
                'budget_id'         => $b?->id,
                'montant_total'     => (float) ($b?->montant_total ?? 0),
                'montant_consomme'  => (float) ($b?->montant_consomme ?? 0),
                'montant_disponible'=> (float) (($b?->montant_total ?? 0) - ($b?->montant_consomme ?? 0)),
                'pourcentage'       => $b && $b->montant_total > 0
                    ? round($b->montant_consomme / $b->montant_total * 100, 1)
                    : 0,
            ];
        });

        // DAPs en attente de signature DF
        $dapsEnAttente = DemandeAutorisationPaiement::with(['expressionBesoin.entreprise'])
            ->where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('expressionBesoin', fn ($q) => $q->where('montant', '>=', $seuilDf))
            ->when($entrepriseId, fn ($q) => $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $entrepriseId)))
            ->whereDoesntHave('validations', fn ($q) => $q->where('niveau_validation_id', $niveauDf->id))
            ->orderBy('created_at')
            ->get(['id', 'reference', 'expression_besoin_id', 'created_at']);

        // Dépenses mensuelles groupe — 12 derniers mois
        $depensesMensuelles = Paiement::select(
                DB::raw("DATE_FORMAT(date_paiement, '%Y-%m') as mois"),
                DB::raw('SUM(montant) as total')
            )
            ->where('date_paiement', '>=', now()->subMonths(11)->startOfMonth())
            ->when($entrepriseId, fn ($q) =>
                $q->whereHas('dap.expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $entrepriseId))
            )
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->mois => (float) $r->total]);

        // Totaux groupe année courante
        $totalPaye = Paiement::query()
            ->whereBetween('date_paiement', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->when($entrepriseId, fn ($q) =>
                $q->whereHas('dap.expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $entrepriseId))
            )
            ->sum('montant');

        $totalEnCours = DemandeAutorisationPaiement::query()
            ->where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($entrepriseId, fn ($q) => $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $entrepriseId)))
            ->sum(DB::raw('(SELECT montant FROM expressions_besoin WHERE expressions_besoin.id = demandes_autorisation_paiement.expression_besoin_id)'));

        return Inertia::render('Dashboard/DF', [
            'niveau'            => $niveauDf,
            'budgetsSocietes'   => $budgetsSocietes,
            'dapsEnAttente'     => $dapsEnAttente,
            'depensesMensuelles'=> $depensesMensuelles,
            'entreprises'       => Entreprise::query()->select('id', 'nom')->orderBy('nom')->get(),
            'filters' => [
                'periode' => $periode,
                'entreprise_id' => $entrepriseId ? (string) $entrepriseId : '',
                'date_debut' => $dateDebutInput,
                'date_fin' => $dateFinInput,
            ],
            'stats' => [
                'en_attente'      => $dapsEnAttente->count(),
                'mes_validees'    => \App\Models\ValidationDap::where('validateur_id', $validateur->id)
                    ->whereBetween('validated_at', [$dateFrom, $dateTo])
                    ->where('statut', \App\Models\ValidationDap::STATUT_APPROUVE)->count(),
                'mes_rejetees'    => \App\Models\ValidationDap::where('validateur_id', $validateur->id)
                    ->whereBetween('validated_at', [$dateFrom, $dateTo])
                    ->where('statut', \App\Models\ValidationDap::STATUT_REJETE)->count(),
                'total_paye'      => (float) $totalPaye,
                'total_en_cours'  => (float) $totalEnCours,
            ],
        ]);
    }

    public function exportDfDashboard(Request $request): BinaryFileResponse
    {
        $user = auth()->user();

        if (!$user || !$user->isDf()) {
            abort(403);
        }

        $niveauDf = $user->niveauValidation;
        $seuilDf = SeuilValidation::where('niveau_validation_id', $niveauDf->id)->value('montant_seuil') ?? 0;

        $filters = [
            'periode' => $request->string('periode')->toString() ?: 'this_month',
            'entreprise_id' => $request->string('entreprise_id')->toString(),
            'date_debut' => $request->string('date_debut')->toString(),
            'date_fin' => $request->string('date_fin')->toString(),
        ];

        return Excel::download(
            new DfPendingDapsExport((int) $niveauDf->id, (float) $seuilDf, $filters),
            'df-daps-en-attente-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function validateurDashboard($user): Response
    {
        $niveau = $user->niveauValidation;
        $seuil  = $niveau
            ? (SeuilValidation::where('niveau_validation_id', $niveau->id)->value('montant_seuil') ?? 0)
            : 0;

        $enAttente = DemandeAutorisationPaiement::where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)
            ->when($niveau, fn ($q) =>
                $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('montant', '>=', $seuil))
                  ->whereDoesntHave('validations', fn ($sq) => $sq->where('niveau_validation_id', $niveau->id))
            )
            ->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'en_attente'   => $enAttente,
                'mes_validees' => \App\Models\ValidationDap::where('validateur_id', $user->id)
                    ->where('statut', \App\Models\ValidationDap::STATUT_APPROUVE)->count(),
                'mes_rejetees' => \App\Models\ValidationDap::where('validateur_id', $user->id)
                    ->where('statut', \App\Models\ValidationDap::STATUT_REJETE)->count(),
            ],
            'niveau' => $niveau,
        ]);
    }

    private function employeDashboard($user): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total'      => $user->expressionsBesoin()->count(),
                'en_attente' => $user->expressionsBesoin()->where('statut', ExpressionBesoin::STATUT_EN_ATTENTE)->count(),
                'validees'   => $user->expressionsBesoin()->where('statut', ExpressionBesoin::STATUT_VALIDEE)->count(),
                'rejetees'   => $user->expressionsBesoin()->where('statut', ExpressionBesoin::STATUT_REJETEE)->count(),
            ],
            'recentEb' => $user->expressionsBesoin()->with('entreprise')->latest()->limit(8)->get(),
        ]);
    }
}
