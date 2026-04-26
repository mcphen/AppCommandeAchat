<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Boutique;
use App\Models\CompteEpargne;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Agent::with(['boutique', 'compte', 'user'])
            ->when($request->filled('boutique_id'), fn ($q) => $q->where('boutique_id', $request->integer('boutique_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->string('search')->toString();
                $q->where(fn ($q2) => $q2->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('matricule', 'like', "%{$s}%"));
            })
            ->latest();

        return Inertia::render('Admin/Agents/Index', [
            'agents'    => $query->paginate(15)->withQueryString(),
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
            'filters'   => [
                'boutique_id' => $request->string('boutique_id')->toString(),
                'search'      => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Agents/Form', [
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'matricule'      => ['required', 'string', 'max:50', 'unique:agents,matricule'],
            'nom'            => ['required', 'string', 'max:100'],
            'prenom'         => ['required', 'string', 'max:100'],
            'telephone'      => ['nullable', 'string', 'max:20'],
            'boutique_id'    => ['nullable', 'exists:boutiques,id'],
            'date_adhesion'  => ['required', 'date'],
            'creer_compte'   => ['sometimes', 'boolean'],
            'email'          => ['nullable', 'email', 'unique:users,email'],
            'password'       => ['nullable', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($data) {
            $userId = null;

            if (! empty($data['creer_compte']) && ! empty($data['email'])) {
                $role = Role::where('slug', 'agent')->first();
                $user = User::create([
                    'name'     => "{$data['prenom']} {$data['nom']}",
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password'] ?? 'password'),
                    'role_id'  => $role?->id,
                ]);
                $userId = $user->id;
            }

            $agent = Agent::create([
                'user_id'       => $userId,
                'boutique_id'   => $data['boutique_id'] ?? null,
                'matricule'     => $data['matricule'],
                'nom'           => $data['nom'],
                'prenom'        => $data['prenom'],
                'telephone'     => $data['telephone'] ?? null,
                'date_adhesion' => $data['date_adhesion'],
            ]);

            CompteEpargne::create([
                'agent_id'   => $agent->id,
                'opened_at'  => $data['date_adhesion'],
            ]);
        });

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent créé avec succès.');
    }

    public function edit(Agent $agent): Response
    {
        return Inertia::render('Admin/Agents/Form', [
            'agent'     => $agent->load(['user', 'boutique']),
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $data = $request->validate([
            'matricule'     => ['required', 'string', 'max:50', Rule::unique('agents', 'matricule')->ignore($agent->id)],
            'nom'           => ['required', 'string', 'max:100'],
            'prenom'        => ['required', 'string', 'max:100'],
            'telephone'     => ['nullable', 'string', 'max:20'],
            'boutique_id'   => ['nullable', 'exists:boutiques,id'],
            'date_adhesion' => ['required', 'date'],
            'is_active'     => ['boolean'],
        ]);

        $agent->update($data);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent mis à jour.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->update(['is_active' => false]);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent désactivé.');
    }
}
