<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoutiqueController extends Controller
{
    public function index(): Response
    {
        $boutiques = Boutique::withCount(['users', 'purchaseOrders'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Boutiques/Index', [
            'boutiques' => $boutiques,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Boutiques/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:boutiques,code'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ]);

        Boutique::create($data);

        return redirect()->route('admin.boutiques.index')
            ->with('success', 'Boutique creee avec succes.');
    }

    public function edit(Boutique $boutique): Response
    {
        return Inertia::render('Admin/Boutiques/Form', [
            'boutique' => $boutique,
        ]);
    }

    public function update(Request $request, Boutique $boutique): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', "unique:boutiques,code,{$boutique->id}"],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
        ]);

        $boutique->update($data);

        return redirect()->route('admin.boutiques.index')
            ->with('success', 'Boutique mise a jour.');
    }

    public function destroy(Boutique $boutique): RedirectResponse
    {
        abort_if(
            $boutique->users()->exists() || $boutique->purchaseOrders()->exists(),
            422,
            'Cette boutique est encore utilisee par des utilisateurs ou des commandes.'
        );

        $boutique->delete();

        return redirect()->route('admin.boutiques.index')
            ->with('success', 'Boutique supprimee.');
    }
}
