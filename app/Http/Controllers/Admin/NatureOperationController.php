<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NatureOperation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class NatureOperationController extends Controller
{
    public function index(): Response
    {
        $natureOperations = NatureOperation::orderBy('name')->get();

        return Inertia::render('Admin/NatureOperations/Index', [
            'natureOperations' => $natureOperations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $slug = NatureOperation::makeSlug($request->string('name')->toString());

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('nature_operations', 'slug')->where(fn ($query) => $query->where('slug', $slug))],
            'description' => 'nullable|string|max:500',
        ], [
            'name.unique' => "Cette nature d'opération existe déjà.",
        ]);

        NatureOperation::create($validated + ['slug' => $slug, 'is_active' => true]);

        return back()->with('success', 'Nature d\'opération créée.');
    }

    public function update(Request $request, NatureOperation $natureOperation): RedirectResponse
    {
        $slug = NatureOperation::makeSlug($request->string('name')->toString());

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('nature_operations', 'slug')->ignore($natureOperation->id)],
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ], [
            'name.unique' => "Cette nature d'opération existe déjà.",
        ]);

        $natureOperation->update($validated + ['slug' => $slug]);

        return back()->with('success', 'Nature d\'opération mise à jour.');
    }

    public function destroy(NatureOperation $natureOperation): RedirectResponse
    {
        abort_if($natureOperation->disbursementRequests()->exists(), 422, 'Impossible de supprimer : des demandes utilisent cette nature.');

        $natureOperation->delete();

        return back()->with('success', 'Nature d\'opération supprimée.');
    }
}
