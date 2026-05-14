<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\Pret;
use App\Models\PretValidationLog;
use App\Models\ValidationLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PretValidationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $this->buildIndexQuery($request, $user);

        return Inertia::render('PretValidations/Index', [
            'prets'      => $query->paginate(10)->withQueryString(),
            'boutiques'  => Boutique::where('is_active', true)->orderBy('name')->get(),
            'levelsCount'=> ValidationLevel::count(),
            'filters'    => ['boutique_id' => $request->string('boutique_id')->toString()],
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $prets = $this->buildIndexQuery($request, $request->user())->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Validations prets');

        $headers = ['Agent', 'Matricule', 'Boutique', 'Montant demandé', 'Motif', 'Niveau courant', 'Soumis le'];
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EA580C');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        foreach ($prets as $row => $pret) {
            $values = [
                trim(($pret->agent?->prenom ?? '') . ' ' . ($pret->agent?->nom ?? '')),
                $pret->agent?->matricule ?? '',
                $pret->agent?->boutique?->name ?? '',
                (float) $pret->montant_demande,
                $pret->motif ?? '',
                $pret->current_level_order ? 'Niveau ' . $pret->current_level_order : '',
                $pret->submitted_at?->format('d/m/Y') ?? '',
            ];
            foreach ($values as $col => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . ($row + 2), $value);
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(fn () => $writer->save('php://output'), 'validations-prets-' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildIndexQuery(Request $request, $user)
    {
        $query = Pret::where('statut', 'pending')
            ->with(['agent.boutique', 'validationLogs'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            $validatableOrders = $user->validatableLevelOrders();
            abort_unless(count($validatableOrders) > 0, 403, 'Vous n\'avez aucun niveau de validation actif.');
            $query->whereIn('current_level_order', $validatableOrders);
        }

        if ($request->filled('boutique_id')) {
            $query->whereHas('agent', fn ($q) => $q->where('boutique_id', $request->integer('boutique_id')));
        }

        return $query;
    }

    public function show(Pret $pret): Response
    {
        $this->authorizeValidation($pret);

        $pret->load(['agent.boutique', 'compteEpargne', 'validationLogs.validationLevel', 'validationLogs.user', 'validationLogs.delegatedBy']);

        return Inertia::render('PretValidations/Show', [
            'pret'   => $pret,
            'levels' => ValidationLevel::orderBy('order')->get(),
        ]);
    }

    public function approve(Pret $pret): RedirectResponse
    {
        $this->authorizeValidation($pret);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $pret->current_level_order)->firstOrFail();

        DB::transaction(function () use ($pret, $user, $currentLevel) {
            PretValidationLog::create([
                'pret_id'             => $pret->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'approved',
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->order),
            ]);

            $nextLevel = ValidationLevel::nextAfter($currentLevel->order);

            if ($nextLevel) {
                $pret->update(['current_level_order' => $nextLevel->order]);
            } else {
                $pret->update([
                    'statut'              => 'approved',
                    'current_level_order' => null,
                ]);
            }
        });

        return redirect()->route('pret-validations.index')
            ->with('success', 'Prêt approuvé.');
    }

    public function reject(Request $request, Pret $pret): RedirectResponse
    {
        $this->authorizeValidation($pret);

        $request->validate(['comment' => ['required', 'string', 'min:10']]);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $pret->current_level_order)->firstOrFail();

        DB::transaction(function () use ($request, $pret, $user, $currentLevel) {
            PretValidationLog::create([
                'pret_id'             => $pret->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'rejected',
                'comment'             => $request->comment,
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->order),
            ]);

            $pret->update([
                'statut'              => 'rejected',
                'current_level_order' => null,
            ]);
        });

        return redirect()->route('pret-validations.index')
            ->with('success', 'Prêt rejeté.');
    }

    private function authorizeValidation(Pret $pret): void
    {
        abort_unless($pret->isPending(), 403, 'Ce prêt n\'est plus en attente de validation.');

        $user = auth()->user();
        if ($user->isAdmin()) return;

        $validatableOrders = $user->validatableLevelOrders();
        abort_unless(count($validatableOrders) > 0, 403, 'Aucun niveau de validation actif.');
        abort_unless(in_array($pret->current_level_order, $validatableOrders), 403, 'Ce prêt n\'est pas à votre niveau.');
    }
}
