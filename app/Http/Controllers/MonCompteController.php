<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonCompteController extends Controller
{
    public function index(Request $request): Response
    {
        $user  = $request->user();
        $agent = $user->agent;

        abort_unless($agent, 403, 'Aucun compte agent associé à cet utilisateur.');

        $agent->load(['boutique']);
        $compte = $agent->compte()->with(['transactions.modeReglement', 'transactions.recorder'])->first();
        $prets  = $agent->prets()->with(['modeReglement', 'remboursements.modeReglement', 'validationLogs.validationLevel'])->get();

        return Inertia::render('MonCompte/Index', [
            'agent'  => $agent,
            'compte' => $compte,
            'prets'  => $prets,
        ]);
    }
}
