<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Boutique;
use App\Models\Role;
use App\Models\User;
use App\Mail\UserAccountCreatedMail;
use App\Models\ValidationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $user = User::create([
            'name'                => $request->name,
            'email'               => $request->email,
            'password'            => Hash::make($request->password),
            'must_change_password' => true,
            'role_id'             => $request->role_id,
            'validation_level_id' => $request->validation_level_id,
            'boutique_id'         => $this->resolveBoutiqueId($request->role_id, $request->boutique_id),
        ]);

        Mail::to($user)->send(new UserAccountCreatedMail($user, $request->password));

        AuditLog::record('user_created', "Compte créé avec le rôle « {$user->role?->name} ».", target: $user);

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
        $previousRole = $user->role;

        $data = [
            'name'                => $request->name,
            'email'               => $request->email,
            'role_id'             => $request->role_id,
            'validation_level_id' => $request->validation_level_id,
            'boutique_id'         => $this->resolveBoutiqueId($request->role_id, $request->boutique_id),
        ];

        $passwordReset = $request->filled('password');

        if ($passwordReset) {
            $data['password'] = Hash::make($request->password);
            $data['must_change_password'] = true;
        }

        $user->update($data);

        if ($previousRole?->id !== $user->role_id) {
            AuditLog::record(
                'user_role_changed',
                "Rôle changé de « {$previousRole?->name} » à « {$user->role?->name} ».",
                target: $user,
            );
        }

        if ($passwordReset) {
            AuditLog::record('user_password_reset_by_admin', 'Mot de passe réinitialisé par un administrateur.', target: $user);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas supprimer votre propre compte.');

        AuditLog::record('user_deleted', "Compte « {$user->name} » ({$user->email}) supprimé.", target: $user);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé.');
    }
    private function resolveBoutiqueId(?string $roleId, ?string $boutiqueId): ?string
    {
        $role = Role::find($roleId);

        return $role?->slug === 'demandeur' ? $boutiqueId : null;
    }
}
