<?php

namespace App\Http\Controllers;

use App\Models\ExpressionBesoin;
use App\Models\ExpressionBesoinAttachment;
use App\Models\User;
use App\Services\ReferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ExpressionBesoinController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $query = ExpressionBesoin::with([
            'user:id,name',
            'entreprise:id,nom',
            'dap:id,expression_besoin_id',
        ])
            ->latest();

        $filters = [
            'search' => (string) $request->string('search')->toString(),
            'statut' => (string) $request->string('statut')->toString(),
            'user_id' => (string) $request->string('user_id')->toString(),
        ];

        if ($user->isEmploye()) {
            $query->where('user_id', $user->id);
        } elseif (!$user->isAdmin() && !$user->isDf()) {
            $query->where('user_id', $user->id);
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('objet', 'like', "%{$search}%")
                    ->orWhere('beneficiaire', 'like', "%{$search}%");
            });
        }

        if ($filters['statut'] !== '') {
            $query->where('statut', $filters['statut']);
        }

        if (($user->isAdmin() || $user->isDf()) && $filters['user_id'] !== '') {
            $query->where('user_id', $filters['user_id']);
        }

        $expressions = $query->paginate(15)->withQueryString();

        return Inertia::render('ExpressionsBesoin/Index', [
            'expressions' => $expressions,
            'filters' => $filters,
            'demandeurs' => ($user->isAdmin() || $user->isDf())
                ? User::query()
                    ->whereHas('expressionsBesoin')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : [],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ExpressionsBesoin/Create');
    }

    public function store(Request $request, ReferenceService $refs): RedirectResponse
    {
        $validated = $request->validate([
            'objet'        => 'required|string|max:255',
            'description'  => 'required|string',
            'montant'      => 'required|numeric|min:0',
            'beneficiaire' => 'nullable|string|max:255',
            'justification' => 'nullable|string',
            'attachments'  => 'nullable|array',
            'attachments.*' => 'file|max:10240',
        ]);

        $user = auth()->user();

        $eb = ExpressionBesoin::create([
            'reference'    => $refs->generateEB(),
            'user_id'      => $user->id,
            'entreprise_id' => $user->entreprise_id,
            'objet'        => $validated['objet'],
            'description'  => $validated['description'],
            'montant'      => $validated['montant'],
            'beneficiaire' => $validated['beneficiaire'] ?? null,
            'justification' => $validated['justification'] ?? null,
            'statut'       => ExpressionBesoin::STATUT_EN_ATTENTE,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('eb-attachments', 'public');
                ExpressionBesoinAttachment::create([
                    'expression_besoin_id' => $eb->id,
                    'nom_fichier'          => $file->getClientOriginalName(),
                    'chemin'               => $path,
                    'type_mime'            => $file->getMimeType(),
                    'taille'               => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('expressions-besoin.show', $eb)
            ->with('success', 'Expression de besoin soumise avec succès.');
    }

    public function show(ExpressionBesoin $expressionsBesoin): Response
    {
        $expressionsBesoin->load(['user', 'entreprise', 'attachments', 'dap.validations.niveauValidation', 'dap.validations.validateur']);

        return Inertia::render('ExpressionsBesoin/Show', [
            'expression' => $expressionsBesoin,
        ]);
    }

    public function downloadAttachment(ExpressionBesoinAttachment $attachment)
    {
        return Storage::disk('public')->download($attachment->chemin, $attachment->nom_fichier);
    }
}
