<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComiteArbitrage;
use App\Models\Entreprise;
use App\Models\MembreComiteArbitrage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComiteArbitrageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/ComitesArbitrage/Index', [
            'comites' => ComiteArbitrage::with(['entreprise', 'membres.user'])
                ->withCount('sessions')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/ComitesArbitrage/Form', [
            'entreprises' => Entreprise::where('is_active', true)->get(),
            'users'       => User::orderBy('name')->get(['id', 'name', 'email', 'fonction']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom'           => 'required|string|max:255',
            'description'   => 'nullable|string',
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'quorum_pct'    => 'required|integer|min:1|max:100',
            'is_active'     => 'boolean',
            'membres'       => 'required|array|min:1',
            'membres.*.user_id'     => 'required|exists:users,id',
            'membres.*.role_membre' => 'required|in:president,membre,secretaire',
        ]);

        $this->validerUnSeulPresident($validated['membres']);

        $comite = ComiteArbitrage::create([
            'nom'           => $validated['nom'],
            'description'   => $validated['description'] ?? null,
            'entreprise_id' => $validated['entreprise_id'] ?? null,
            'quorum_pct'    => $validated['quorum_pct'],
            'is_active'     => $validated['is_active'] ?? true,
            'created_by'    => auth()->id(),
        ]);

        foreach ($validated['membres'] as $m) {
            MembreComiteArbitrage::create([
                'comite_arbitrage_id' => $comite->id,
                'user_id'             => $m['user_id'],
                'role_membre'         => $m['role_membre'],
                'is_active'           => true,
            ]);
        }

        return redirect()->route('admin.comites-arbitrage.index')->with('success', 'Comité d\'arbitrage créé.');
    }

    public function edit(ComiteArbitrage $comitesArbitrage): Response
    {
        return Inertia::render('Admin/ComitesArbitrage/Form', [
            'comite'      => $comitesArbitrage->load('membres.user'),
            'entreprises' => Entreprise::where('is_active', true)->get(),
            'users'       => User::orderBy('name')->get(['id', 'name', 'email', 'fonction']),
        ]);
    }

    public function update(Request $request, ComiteArbitrage $comitesArbitrage): RedirectResponse
    {
        $validated = $request->validate([
            'nom'           => 'required|string|max:255',
            'description'   => 'nullable|string',
            'entreprise_id' => 'nullable|exists:entreprises,id',
            'quorum_pct'    => 'required|integer|min:1|max:100',
            'is_active'     => 'boolean',
            'membres'       => 'required|array|min:1',
            'membres.*.user_id'     => 'required|exists:users,id',
            'membres.*.role_membre' => 'required|in:president,membre,secretaire',
        ]);

        $this->validerUnSeulPresident($validated['membres']);

        $comitesArbitrage->update([
            'nom'           => $validated['nom'],
            'description'   => $validated['description'] ?? null,
            'entreprise_id' => $validated['entreprise_id'] ?? null,
            'quorum_pct'    => $validated['quorum_pct'],
            'is_active'     => $validated['is_active'] ?? true,
        ]);

        $comitesArbitrage->membres()->delete();
        foreach ($validated['membres'] as $m) {
            MembreComiteArbitrage::create([
                'comite_arbitrage_id' => $comitesArbitrage->id,
                'user_id'             => $m['user_id'],
                'role_membre'         => $m['role_membre'],
                'is_active'           => true,
            ]);
        }

        return redirect()->route('admin.comites-arbitrage.index')->with('success', 'Comité mis à jour.');
    }

    public function destroy(ComiteArbitrage $comitesArbitrage): RedirectResponse
    {
        if ($comitesArbitrage->sessions()->whereIn('statut', ['brouillon', 'en_vote'])->exists()) {
            return back()->with('error', 'Impossible de supprimer un comité avec des sessions actives.');
        }

        $comitesArbitrage->delete();
        return redirect()->route('admin.comites-arbitrage.index')->with('success', 'Comité supprimé.');
    }

    private function validerUnSeulPresident(array $membres): void
    {
        $presidents = collect($membres)->where('role_membre', 'president')->count();
        if ($presidents !== 1) {
            abort(422, 'Le comité doit avoir exactement un président.');
        }
    }
}
