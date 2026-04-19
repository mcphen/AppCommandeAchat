<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NiveauValidation;
use App\Models\User;
use App\Models\Validateur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ValidateurController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Validateurs/Index', [
            'validateurs' => Validateur::with(['user', 'niveauValidation'])->get(),
            'niveaux'     => NiveauValidation::orderBy('ordre')->get(),
            'users'       => User::whereHas('role', fn ($q) => $q->where('slug', 'validateur'))->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'              => 'required|exists:users,id',
            'niveau_validation_id' => 'required|exists:niveaux_validation,id',
        ]);

        Validateur::updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['niveau_validation_id' => $validated['niveau_validation_id'], 'is_active' => true]
        );

        return redirect()->route('admin.validateurs.index')->with('success', 'Validateur assigné.');
    }

    public function destroy(Validateur $validateur): RedirectResponse
    {
        $validateur->delete();
        return redirect()->route('admin.validateurs.index')->with('success', 'Validateur retiré.');
    }
}
