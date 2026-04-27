<?php

namespace App\Http\Controllers;

use App\Models\SessionArbitrage;
use App\Models\VoteArbitrage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoteArbitrageController extends Controller
{
    public function store(Request $request, SessionArbitrage $session): RedirectResponse
    {
        if ($session->statut !== SessionArbitrage::STATUT_EN_VOTE) {
            return back()->with('error', 'Les votes ne sont pas ouverts pour cette session.');
        }

        $userId = auth()->id();

        $estMembre = $session->comite->membres()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();

        $isAdmin = auth()->user()->role?->slug === 'admin';

        if (! $estMembre && ! $isAdmin) {
            return back()->with('error', 'Vous n\'êtes pas membre de ce comité.');
        }

        $dapIds = $session->sessionDaps()->pluck('dap_id')->toArray();
        $n      = count($dapIds);

        $validated = $request->validate([
            'votes'               => "required|array|size:{$n}",
            'votes.*.dap_id'      => 'required|in:' . implode(',', $dapIds),
            'votes.*.rang'        => "required|integer|min:1|max:{$n}",
            'votes.*.commentaire' => 'nullable|string|max:500',
        ]);

        $rangsUtilises = collect($validated['votes'])->pluck('rang')->sort()->values()->toArray();
        if ($rangsUtilises !== range(1, $n)) {
            return back()->with('error', 'Chaque rang de 1 à ' . $n . ' doit être utilisé exactement une fois.');
        }

        VoteArbitrage::where('session_arbitrage_id', $session->id)->where('user_id', $userId)->delete();

        foreach ($validated['votes'] as $v) {
            VoteArbitrage::create([
                'session_arbitrage_id' => $session->id,
                'dap_id'               => $v['dap_id'],
                'user_id'              => $userId,
                'rang'                 => $v['rang'],
                'commentaire'          => $v['commentaire'] ?? null,
                'voted_at'             => now(),
            ]);
        }

        return back()->with('success', 'Votre vote a été enregistré.');
    }
}
