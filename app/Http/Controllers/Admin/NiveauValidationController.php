<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NiveauValidation;
use App\Models\SeuilValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NiveauValidationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/NiveauxValidation/Index', [
            'niveaux' => NiveauValidation::with('seuil')->withCount('validateurs')->orderBy('ordre')->get(),
        ]);
    }

    public function update(Request $request, NiveauValidation $niveauValidation): RedirectResponse
    {
        $validated = $request->validate([
            'montant_seuil' => 'required|numeric|min:0',
        ]);

        SeuilValidation::updateOrCreate(
            ['niveau_validation_id' => $niveauValidation->id],
            ['montant_seuil' => $validated['montant_seuil']]
        );

        return redirect()->route('admin.niveaux-validation.index')->with('success', 'Seuil mis à jour.');
    }
}
