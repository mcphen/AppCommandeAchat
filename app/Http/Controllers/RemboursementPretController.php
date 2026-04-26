<?php

namespace App\Http\Controllers;

use App\Models\Pret;
use App\Models\RemboursementPret;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RemboursementPretController extends Controller
{
    public function store(Request $request, Pret $pret): RedirectResponse
    {
        $user = $request->user();

        abort_unless($pret->canBeRembourse(), 403, 'Ce prêt ne peut pas être remboursé.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($pret->agent->boutique_id === $user->boutique_id, 403);
        }

        $resteARembourser = $pret->resteARembourser();

        $validated = $request->validate([
            'montant'            => ['required', 'numeric', 'min:1', "max:{$resteARembourser}"],
            'mode_reglement_id'  => ['required', 'exists:modes_reglement,id'],
            'reference'          => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'remboursement_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated, $pret, $user) {
            RemboursementPret::create([
                ...$validated,
                'pret_id'     => $pret->id,
                'recorded_by' => $user->id,
            ]);

            $nouveauCapital = (float) $pret->remboursements()->sum('montant');
            $montantAccorde = (float) $pret->montant_accorde;

            if ($nouveauCapital >= $montantAccorde) {
                $pret->update([
                    'statut'   => 'solde',
                    'solde_at' => now(),
                ]);
                // Libérer l'épargne bloquée
                $pret->compteEpargne->update([
                    'solde_bloque' => max(0, (float) $pret->compteEpargne->solde_bloque - $montantAccorde),
                ]);
            }
        });

        return redirect()->route('caisse.prets.show', $pret)
            ->with('success', 'Remboursement enregistré.');
    }
}
