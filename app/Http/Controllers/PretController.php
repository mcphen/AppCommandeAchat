<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Boutique;
use App\Models\ModeReglement;
use App\Models\Pret;
use App\Models\ValidationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PretController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $this->buildIndexQuery($request, $user);

        return Inertia::render('Caisse/Prets/Index', [
            'prets'     => $query->paginate(15)->withQueryString(),
            'boutiques' => $user->isAdmin() ? Boutique::where('is_active', true)->orderBy('name')->get() : [],
            'filters'   => $this->getFilters($request),
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $prets = $this->buildIndexQuery($request, $request->user())->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prets');

        $headers = ['Agent', 'Matricule', 'Boutique', 'Montant demandé', 'Montant accordé', 'Statut', 'Mode', 'Créé le'];
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EA580C');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $labels = [
            'draft' => 'Brouillon',
            'pending' => 'En validation',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'decaisse' => 'Décaissé',
            'solde' => 'Soldé',
        ];

        foreach ($prets as $row => $pret) {
            $values = [
                trim(($pret->agent?->prenom ?? '') . ' ' . ($pret->agent?->nom ?? '')),
                $pret->agent?->matricule ?? '',
                $pret->agent?->boutique?->name ?? '',
                (float) $pret->montant_demande,
                (float) ($pret->montant_accorde ?? 0),
                $labels[$pret->statut] ?? $pret->statut,
                $pret->modeReglement?->name ?? '',
                $pret->created_at?->format('d/m/Y') ?? '',
            ];
            foreach ($values as $col => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . ($row + 2), $value);
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(fn () => $writer->save('php://output'), 'prets-' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildIndexQuery(Request $request, $user)
    {
        return Pret::with(['agent.boutique', 'modeReglement'])
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->whereHas('agent', fn ($a) => $a->where('boutique_id', $user->boutique_id)))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')->toString()))
            ->when($request->filled('boutique_id'), fn ($q) => $q->whereHas('agent', fn ($a) => $a->where('boutique_id', $request->integer('boutique_id'))))
            ->latest();
    }

    private function getFilters(Request $request): array
    {
        return [
            'statut'      => $request->string('statut')->toString(),
            'boutique_id' => $request->string('boutique_id')->toString(),
        ];
    }

    public function create(Request $request): Response
    {
        $agentId = $request->integer('agent_id');
        $agent   = $agentId ? Agent::with('compte')->findOrFail($agentId) : null;

        $user = $request->user();
        if ($user->isCaissier() && $user->boutique_id && $agent) {
            abort_unless($agent->boutique_id === $user->boutique_id, 403);
        }

        $agents = Agent::where('is_active', true)
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->where('boutique_id', $user->boutique_id))
            ->orderBy('nom')->orderBy('prenom')
            ->with('compte')
            ->get();

        return Inertia::render('Caisse/Prets/Form', [
            'agent'  => $agent,
            'agents' => $agents,
            'modes'  => ModeReglement::actifs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'agent_id'       => ['required', 'exists:agents,id'],
            'montant_demande' => ['required', 'numeric', 'min:1'],
            'motif'          => ['nullable', 'string', 'max:500'],
        ]);

        $agent  = Agent::with('compte')->findOrFail($validated['agent_id']);
        $compte = $agent->compte;

        abort_unless($compte && $compte->is_active, 422, 'Compte épargne inactif.');
        abort_unless(! $agent->pretActif(), 422, 'Cet agent a déjà un prêt en cours.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($agent->boutique_id === $user->boutique_id, 403);
        }

        $pret = Pret::create([
            ...$validated,
            'compte_epargne_id' => $compte->id,
            'statut'            => 'draft',
            'recorded_by'       => $user->id,
        ]);

        return redirect()->route('caisse.prets.show', $pret)
            ->with('success', 'Demande de prêt créée.');
    }

    public function show(Pret $pret): Response
    {
        $user = auth()->user();
        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($pret->agent->boutique_id === $user->boutique_id, 403);
        }

        $pret->load(['agent.boutique', 'modeReglement', 'remboursements.modeReglement', 'remboursements.recorder', 'validationLogs.validationLevel', 'validationLogs.user']);

        return Inertia::render('Caisse/Prets/Show', [
            'pret'   => $pret,
            'modes'  => ModeReglement::actifs(),
            'levels' => ValidationLevel::orderBy('order')->get(),
        ]);
    }

    public function submit(Pret $pret): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($pret->canBeSubmitted(), 403, 'Ce prêt ne peut pas être soumis.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($pret->agent->boutique_id === $user->boutique_id, 403);
        }

        $firstLevel = ValidationLevel::orderBy('order')->first();
        abort_unless($firstLevel, 422, 'Aucun niveau de validation configuré.');

        $pret->update([
            'statut'              => 'pending',
            'current_level_order' => $firstLevel->order,
            'submitted_at'        => now(),
        ]);

        return redirect()->route('caisse.prets.show', $pret)
            ->with('success', 'Demande soumise au circuit de validation.');
    }

    public function decaisser(Request $request, Pret $pret): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($pret->canBeDecaisse(), 403, 'Ce prêt ne peut pas être décaissé.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($pret->agent->boutique_id === $user->boutique_id, 403);
        }

        $validated = $request->validate([
            'montant_accorde'   => ['required', 'numeric', 'min:1'],
            'mode_reglement_id' => ['required', 'exists:modes_reglement,id'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $pret) {
            $pret->update([
                'statut'            => 'decaisse',
                'montant_accorde'   => $validated['montant_accorde'],
                'mode_reglement_id' => $validated['mode_reglement_id'],
                'decaisse_at'       => now(),
            ]);

            // Bloquer l'épargne comme garantie
            $pret->compteEpargne->increment('solde_bloque', $validated['montant_accorde']);
        });

        return redirect()->route('caisse.prets.show', $pret)
            ->with('success', 'Prêt décaissé avec succès.');
    }
}
