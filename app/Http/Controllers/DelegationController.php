<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ValidationDelegation;
use App\Models\ValidationLevel;
use App\Notifications\DelegationReceivedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DelegationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $given = ValidationDelegation::with(['delegatee', 'validationLevel.circuit'])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('delegator_id', $user->id))
            ->orderByDesc('created_at')
            ->get();

        $received = ValidationDelegation::with(['delegator', 'validationLevel.circuit'])
            ->where('delegatee_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $users = User::whereHas('role')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $levels = ValidationLevel::with('circuit')->orderBy('circuit_id')->orderBy('order')->get();

        return Inertia::render('Delegations/Index', [
            'given'    => $given,
            'received' => $received,
            'users'    => $users,
            'levels'   => $levels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'delegatee_id'        => ['required', 'integer', 'exists:users,id', 'different:' . $user->id],
            'validation_level_id' => ['required', 'integer', 'exists:validation_levels,id'],
            'starts_at'           => ['required', 'date'],
            'ends_at'             => ['required', 'date', 'after_or_equal:starts_at'],
            'reason'              => ['nullable', 'string', 'max:255'],
        ]);

        // Admin peut déléguer pour n'importe quel validateur
        // Validateur ne peut déléguer que son propre niveau
        if (! $user->isAdmin()) {
            abort_unless(
                $user->validationLevels->contains('id', $data['validation_level_id']),
                403,
                'Vous ne pouvez déléguer que votre propre niveau de validation.'
            );
        }

        $delegation = ValidationDelegation::create([
            'delegator_id'        => $user->id,
            'delegatee_id'        => $data['delegatee_id'],
            'validation_level_id' => $data['validation_level_id'],
            'starts_at'           => $data['starts_at'],
            'ends_at'             => $data['ends_at'],
            'reason'              => $data['reason'] ?? null,
            'is_active'           => true,
        ]);

        $delegation->delegatee->notify(new DelegationReceivedNotification($delegation->load(['delegator', 'delegatee', 'validationLevel'])));

        return redirect()->route('delegations.index')
            ->with('success', 'Délégation créée et notifiée à ' . $delegation->delegatee->name . '.');
    }

    public function toggle(ValidationDelegation $delegation): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $delegation->delegator_id === $user->id, 403);

        $delegation->update(['is_active' => ! $delegation->is_active]);

        $label = $delegation->is_active ? 'activée' : 'désactivée';

        return redirect()->route('delegations.index')
            ->with('success', "Délégation {$label}.");
    }

    public function destroy(ValidationDelegation $delegation): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $delegation->delegator_id === $user->id, 403);

        $delegation->delete();

        return redirect()->route('delegations.index')
            ->with('success', 'Délégation supprimée.');
    }
}
