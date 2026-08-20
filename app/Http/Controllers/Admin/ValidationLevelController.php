<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Circuit;
use App\Models\ValidationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ValidationLevelController extends Controller
{
    public function index(): Response
    {
        $levels = ValidationLevel::with('circuit')
            ->withCount('validators')
            ->orderBy('circuit_id')
            ->orderBy('order')
            ->get();

        return Inertia::render('Admin/ValidationLevels/Index', [
            'levels'   => $levels,
            'circuits' => Circuit::orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $circuitId = $request->integer('circuit_id') ?: null;
        $nextOrder = $circuitId
            ? (ValidationLevel::where('circuit_id', $circuitId)->max('order') ?? 0) + 1
            : 1;

        return Inertia::render('Admin/ValidationLevels/Form', [
            'nextOrder' => $nextOrder,
            'circuits'  => Circuit::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'circuit_id'  => ['required', 'integer', 'exists:circuits,id'],
            'name'        => ['required', 'string', 'max:255'],
            'order'       => [
                'required', 'integer', 'min:1',
                Rule::unique('validation_levels', 'order')->where(fn ($q) => $q->where('circuit_id', $request->integer('circuit_id'))),
            ],
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
            'level'    => $validationLevel,
            'circuits' => Circuit::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ValidationLevel $validationLevel): RedirectResponse
    {
        $data = $request->validate([
            'circuit_id'  => ['required', 'integer', 'exists:circuits,id'],
            'name'        => ['required', 'string', 'max:255'],
            'order'       => [
                'required', 'integer', 'min:1',
                Rule::unique('validation_levels', 'order')
                    ->where(fn ($q) => $q->where('circuit_id', $request->integer('circuit_id')))
                    ->ignore($validationLevel->id),
            ],
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
