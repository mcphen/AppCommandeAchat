<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use App\Models\Groupe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EntrepriseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Entreprises/Index', [
            'entreprises' => Entreprise::with('groupe')->withCount('users')->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Entreprises/Form', [
            'groupes' => Groupe::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'groupe_id' => 'required|exists:groupes,id',
            'nom'       => 'required|string|max:255',
            'code'      => 'required|string|max:50|unique:entreprises',
            'adresse'   => 'nullable|string|max:255',
            'ville'     => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        Entreprise::create($validated);

        return redirect()->route('admin.entreprises.index')->with('success', 'Entreprise créée.');
    }

    public function edit(Entreprise $entreprise): Response
    {
        return Inertia::render('Admin/Entreprises/Form', [
            'entreprise' => $entreprise,
            'groupes'    => Groupe::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Entreprise $entreprise): RedirectResponse
    {
        $validated = $request->validate([
            'groupe_id' => 'required|exists:groupes,id',
            'nom'       => 'required|string|max:255',
            'code'      => 'required|string|max:50|unique:entreprises,code,' . $entreprise->id,
            'adresse'   => 'nullable|string|max:255',
            'ville'     => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $entreprise->update($validated);

        return redirect()->route('admin.entreprises.index')->with('success', 'Entreprise mise à jour.');
    }

    public function destroy(Entreprise $entreprise): RedirectResponse
    {
        $entreprise->delete();
        return redirect()->route('admin.entreprises.index')->with('success', 'Entreprise supprimée.');
    }
}
