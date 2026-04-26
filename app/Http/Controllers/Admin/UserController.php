<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Boutique;
use App\Models\Role;
use App\Models\User;
use App\Models\ValidationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with(['role', 'validationLevel', 'boutique'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'roles'     => Role::all(),
            'levels'    => ValidationLevel::orderBy('order')->get(),
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name'                => $request->name,
            'email'               => $request->email,
            'password'            => Hash::make($request->password),
            'role_id'             => $request->role_id,
            'validation_level_id' => $request->validation_level_id,
            'boutique_id'         => $this->resolveBoutiqueId($request->role_id, $request->boutique_id),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user'      => $user->load(['role', 'validationLevel', 'boutique']),
            'roles'     => Role::all(),
            'levels'    => ValidationLevel::orderBy('order')->get(),
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name'                => $request->name,
            'email'               => $request->email,
            'role_id'             => $request->role_id,
            'validation_level_id' => $request->validation_level_id,
            'boutique_id'         => $this->resolveBoutiqueId($request->role_id, $request->boutique_id),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas supprimer votre propre compte.');

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé.');
    }
    private function resolveBoutiqueId(?string $roleId, ?string $boutiqueId): ?string
    {
        $role = Role::find($roleId);

        return in_array($role?->slug, ['demandeur', 'caissier']) ? $boutiqueId : null;
    }
}
