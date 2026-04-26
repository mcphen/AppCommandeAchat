<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionEpargneController extends Controller
{
    public function store(Request $request, Agent $agent): RedirectResponse
    {
        $user = $request->user();

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($agent->boutique_id === $user->boutique_id, 403);
        }

        $compte = $agent->compte;
        abort_unless($compte && $compte->is_active, 403, 'Compte épargne inactif.');

        $validated = $request->validate([
            'type'               => ['required', 'in:depot,retrait'],
            'montant'            => ['required', 'numeric', 'min:1'],
            'mode_reglement_id'  => ['required', 'exists:modes_reglement,id'],
            'reference'          => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'transaction_date'   => ['required', 'date'],
        ]);

        if ($validated['type'] === 'retrait') {
            abort_unless(
                $validated['montant'] <= $compte->soldeDispo(),
                422,
                'Montant supérieur au solde disponible.'
            );
        }

        DB::transaction(function () use ($validated, $compte, $user) {
            $compte->transactions()->create([
                ...$validated,
                'recorded_by' => $user->id,
            ]);

            if ($validated['type'] === 'depot') {
                $compte->increment('solde_epargne', $validated['montant']);
            } else {
                $compte->decrement('solde_epargne', $validated['montant']);
            }
        });

        return redirect()->route('caisse.agents.show', $agent)
            ->with('success', ucfirst($validated['type']) . ' enregistré avec succès.');
    }
}
