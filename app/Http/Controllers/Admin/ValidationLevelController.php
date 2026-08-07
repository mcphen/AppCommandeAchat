<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ValidationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ValidationLevelController extends Controller
{
    public function index(): Response
    {
        $levels = ValidationLevel::withCount('validators')
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/ValidationLevels/Index', [
            'levels' => $levels,
        ]);
    }

    public function create(): Response
    {
        $nextOrder = (ValidationLevel::max('order') ?? 0) + 1;

        return Inertia::render('Admin/ValidationLevels/Form', [
            'nextOrder' => $nextOrder,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'order'       => ['required', 'integer', 'min:1', 'unique:validation_levels,order'],
            'description' => ['nullable', 'string', 'max:500'],
            'type'        => ['required', 'in:validation,approbation'],
        ]);

        ValidationLevel::create($data);

        return redirect()->route('admin.validation-levels.index')
            ->with('success', 'Niveau de validation créé.');
    }

    public function edit(ValidationLevel $validationLevel): Response
    {
        return Inertia::render('Admin/ValidationLevels/Form', [
            'level' => $validationLevel,
        ]);
    }

    public function update(Request $request, ValidationLevel $validationLevel): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'order'       => ['required', 'integer', 'min:1', "unique:validation_levels,order,{$validationLevel->id}"],
            'description' => ['nullable', 'string', 'max:500'],
            'type'        => ['required', 'in:validation,approbation'],
        ]);

        $validationLevel->update($data);

        return redirect()->route('admin.validation-levels.index')
            ->with('success', 'Niveau de validation mis à jour.');
    }

    public function destroy(ValidationLevel $validationLevel): RedirectResponse
    {
        abort_if(
            $validationLevel->validators()->exists(),
            422,
            'Ce niveau a des validateurs assignés. Réassignez-les avant de supprimer.'
        );

        $validationLevel->delete();

        return redirect()->route('admin.validation-levels.index')
            ->with('success', 'Niveau supprimé.');
    }
}
