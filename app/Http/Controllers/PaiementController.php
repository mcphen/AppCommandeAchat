<?php

namespace App\Http\Controllers;

use App\Models\DemandeAutorisationPaiement;
use App\Models\Paiement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaiementController extends Controller
{
    public function index(): Response
    {
        $paiements = Paiement::with([
                'dap.expressionBesoin.user',
                'dap.expressionBesoin.entreprise',
                'saisie_par',
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('Paiements/Index', [
            'paiements' => $paiements,
        ]);
    }

    public function store(Request $request, DemandeAutorisationPaiement $dap): RedirectResponse
    {
        $validated = $request->validate([
            'montant'       => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|in:' . implode(',', Paiement::MODES),
            'reference'     => 'nullable|string|max:255',
            'banque'        => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
        ]);

        Paiement::create(array_merge($validated, [
            'dap_id'     => $dap->id,
            'created_by' => auth()->id(),
        ]));

        $dap->update(['statut' => DemandeAutorisationPaiement::STATUT_PAYEE]);

        return redirect()->route('validations-dap.show', $dap)
            ->with('success', 'Paiement enregistré avec succès.');
    }
}
