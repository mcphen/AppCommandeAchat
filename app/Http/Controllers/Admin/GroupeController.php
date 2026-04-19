<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Groupe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Groupes/Index', [
            'groupes' => Groupe::withCount('entreprises')->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Groupes/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom'       => 'required|string|max:255',
            'code'      => 'required|string|max:50|unique:groupes',
            'is_active' => 'boolean',
        ]);

        Groupe::create($validated);

        return redirect()->route('admin.groupes.index')->with('success', 'Groupe créé.');
    }

    public function edit(Groupe $groupe): Response
    {
        return Inertia::render('Admin/Groupes/Form', ['groupe' => $groupe]);
    }

    public function update(Request $request, Groupe $groupe): RedirectResponse
    {
        $validated = $request->validate([
            'nom'       => 'required|string|max:255',
            'code'      => 'required|string|max:50|unique:groupes,code,' . $groupe->id,
            'is_active' => 'boolean',
        ]);

        $groupe->update($validated);

        return redirect()->route('admin.groupes.index')->with('success', 'Groupe mis à jour.');
    }

    public function destroy(Groupe $groupe): RedirectResponse
    {
        $groupe->delete();
        return redirect()->route('admin.groupes.index')->with('success', 'Groupe supprimé.');
    }
}
