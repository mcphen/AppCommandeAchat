<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Circuit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CircuitController extends Controller
{
    public function index(): Response
    {
        $circuits = Circuit::withCount(['validationLevels', 'purchaseOrders'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Circuits/Index', [
            'circuits' => $circuits,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Circuits/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', 'unique:circuits,code'],
            'name'      => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        Circuit::create($data);

        return redirect()->route('admin.circuits.index')
            ->with('success', 'Circuit créé.');
    }

    public function edit(Circuit $circuit): Response
    {
        return Inertia::render('Admin/Circuits/Form', [
            'circuit' => $circuit,
        ]);
    }

    public function update(Request $request, Circuit $circuit): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', "unique:circuits,code,{$circuit->id}"],
            'name'      => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $circuit->update($data);

        return redirect()->route('admin.circuits.index')
            ->with('success', 'Circuit mis à jour.');
    }

    public function destroy(Circuit $circuit): RedirectResponse
    {
        abort_if(
            $circuit->validationLevels()->exists() || $circuit->purchaseOrders()->exists(),
            422,
            'Ce circuit est encore utilisé par des niveaux de validation ou des commandes.'
        );

        $circuit->delete();

        return redirect()->route('admin.circuits.index')
            ->with('success', 'Circuit supprimé.');
    }
}
