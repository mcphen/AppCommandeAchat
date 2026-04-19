<?php

namespace App\Http\Controllers;

use App\Models\BudgetAnnuel;
use App\Models\DemandeAutorisationPaiement;
use App\Models\ExpressionBesoin;
use App\Models\NiveauValidation;
use App\Models\ValidationDap;
use App\Services\ReferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComptaController extends Controller
{
    public function index(): Response
    {
        $expressions = ExpressionBesoin::with(['user', 'entreprise', 'dap'])
            ->where('statut', ExpressionBesoin::STATUT_EN_ATTENTE)
            ->latest()
            ->paginate(15);

        return Inertia::render('Compta/Index', [
            'expressions' => $expressions,
        ]);
    }

    public function show(ExpressionBesoin $expression): Response
    {
        $expression->load(['user', 'entreprise', 'attachments']);
        $budgets = BudgetAnnuel::where('entreprise_id', $expression->entreprise_id)
            ->where('annee', now()->year)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Compta/Show', [
            'expression' => $expression,
            'budgets'    => $budgets,
        ]);
    }

    public function valider(Request $request, ExpressionBesoin $expression, ReferenceService $refs): RedirectResponse
    {
        $validated = $request->validate([
            'budget_annuel_id' => 'nullable|exists:budgets_annuels,id',
            'notes'            => 'nullable|string',
        ]);

        $niveauCompta = NiveauValidation::where('slug', 'compta')->firstOrFail();

        // Marquer l'EB comme validée
        $expression->update(['statut' => ExpressionBesoin::STATUT_VALIDEE]);

        // Créer la DAP
        $dap = DemandeAutorisationPaiement::create([
            'reference'           => $refs->generateDAP(),
            'expression_besoin_id' => $expression->id,
            'budget_annuel_id'    => $validated['budget_annuel_id'] ?? null,
            'created_by'          => auth()->id(),
            'statut'              => DemandeAutorisationPaiement::STATUT_EN_COURS,
            'notes'               => $validated['notes'] ?? null,
        ]);

        // Enregistrer la validation compta
        ValidationDap::create([
            'dap_id'               => $dap->id,
            'niveau_validation_id' => $niveauCompta->id,
            'validateur_id'        => auth()->id(),
            'statut'               => ValidationDap::STATUT_APPROUVE,
            'validated_at'         => now(),
        ]);

        // Mettre à jour le montant consommé du budget
        if ($validated['budget_annuel_id']) {
            BudgetAnnuel::find($validated['budget_annuel_id'])
                ->increment('montant_consomme', $expression->montant);
        }

        return redirect()->route('compta.index')
            ->with('success', "DAP {$dap->reference} créée avec succès.");
    }

    public function rejeter(Request $request, ExpressionBesoin $expression): RedirectResponse
    {
        $validated = $request->validate([
            'motif_rejet' => 'required|string',
        ]);

        $expression->update([
            'statut'      => ExpressionBesoin::STATUT_REJETEE,
            'motif_rejet' => $validated['motif_rejet'],
            'rejected_at' => now(),
        ]);

        return redirect()->route('compta.index')
            ->with('success', 'Expression de besoin rejetée.');
    }
}
