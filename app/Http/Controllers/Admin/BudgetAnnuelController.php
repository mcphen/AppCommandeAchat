<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetAnnuel;
use App\Models\Entreprise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetAnnuelController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/BudgetsAnnuels/Index', [
            'budgets' => BudgetAnnuel::with('entreprise')->latest()->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/BudgetsAnnuels/Form', [
            'entreprises' => Entreprise::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id',
            'annee'         => 'required|integer|min:2020|max:2099',
            'libelle'       => 'required|string|max:255',
            'montant_total' => 'required|numeric|min:0',
            'is_active'     => 'boolean',
        ]);

        BudgetAnnuel::create($validated);

        return redirect()->route('admin.budgets-annuels.index')->with('success', 'Budget créé.');
    }

    public function edit(BudgetAnnuel $budgetsAnnuel): Response
    {
        return Inertia::render('Admin/BudgetsAnnuels/Form', [
            'budget'      => $budgetsAnnuel,
            'entreprises' => Entreprise::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, BudgetAnnuel $budgetsAnnuel): RedirectResponse
    {
        $validated = $request->validate([
            'entreprise_id' => 'required|exists:entreprises,id',
            'annee'         => 'required|integer|min:2020|max:2099',
            'libelle'       => 'required|string|max:255',
            'montant_total' => 'required|numeric|min:0',
            'is_active'     => 'boolean',
        ]);

        $budgetsAnnuel->update($validated);

        return redirect()->route('admin.budgets-annuels.index')->with('success', 'Budget mis à jour.');
    }

    public function destroy(BudgetAnnuel $budgetsAnnuel): RedirectResponse
    {
        $budgetsAnnuel->delete();
        return redirect()->route('admin.budgets-annuels.index')->with('success', 'Budget supprimé.');
    }
}
