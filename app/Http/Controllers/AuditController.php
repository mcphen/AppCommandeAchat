<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\User;
use App\Models\ValidationLevel;
use App\Models\ValidationLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $user  = $request->user();
        $query = $this->buildQuery($request, $user);

        $logs = $query->paginate(20)->withQueryString();

        // Statistiques globales (mêmes filtres, sans pagination)
        $statsQuery = $this->buildQuery($request, $user);
        $stats = [
            'total'          => $statsQuery->count(),
            'approved'       => (clone $statsQuery)->where('action', 'approved')->count(),
            'rejected'       => (clone $statsQuery)->where('action', 'rejected')->count(),
            'orders_touched' => (clone $statsQuery)->distinct('purchase_order_id')->count('purchase_order_id'),
        ];

        return Inertia::render('Audit/Index', [
            'logs'       => $logs,
            'stats'      => $stats,
            'boutiques'  => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'levels'     => ValidationLevel::orderBy('order')->get(['id', 'name', 'order']),
            'validators' => $user->isAdmin()
                ? User::whereHas('role', fn ($q) => $q->where('slug', 'validateur'))
                    ->orWhereHas('role', fn ($q) => $q->where('slug', 'admin'))
                    ->orderBy('name')->get(['id', 'name'])
                : [],
            'filters' => $this->getFilters($request),
        ]);
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel']), 404);

        $user = $request->user();
        $logs = $this->buildQuery($request, $user)->get();

        $statsQuery = $this->buildQuery($request, $user);
        $stats = [
            'total'          => $logs->count(),
            'approved'       => $logs->where('action', 'approved')->count(),
            'rejected'       => $logs->where('action', 'rejected')->count(),
            'orders_touched' => $logs->pluck('purchase_order_id')->unique()->count(),
        ];

        $filters = $this->getFilters($request);

        return match ($format) {
            'pdf'   => $this->exportPdf($logs, $stats, $filters),
            'excel' => $this->exportExcel($logs, $stats),
        };
    }

    private function buildQuery(Request $request, $user)
    {
        $query = ValidationLog::with([
            'purchaseOrder.boutique',
            'purchaseOrder.user',
            'validationLevel',
            'user',
            'delegatedBy',
        ])->latest();

        // Validateur : uniquement ses propres actions
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('boutique_id')) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('boutique_id', $request->integer('boutique_id')));
        }

        if ($request->filled('level_id')) {
            $query->where('validation_level_id', $request->integer('level_id'));
        }

        if ($request->filled('validator_id') && $user->isAdmin()) {
            $query->where('user_id', $request->integer('validator_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('title', 'like', "%{$search}%"));
        }

        return $query;
    }

    private function getFilters(Request $request): array
    {
        return [
            'action'       => $request->string('action')->toString(),
            'boutique_id'  => $request->string('boutique_id')->toString(),
            'level_id'     => $request->string('level_id')->toString(),
            'validator_id' => $request->string('validator_id')->toString(),
            'date_from'    => $request->string('date_from')->toString(),
            'date_to'      => $request->string('date_to')->toString(),
            'search'       => $request->string('search')->toString(),
        ];
    }

    private function exportPdf($logs, $stats, $filters)
    {
        $pdf = Pdf::loadView('pdf.audit_report', [
            'logs'    => $logs,
            'stats'   => $stats,
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit-validations-' . now()->format('Y-m-d') . '.pdf');
    }

    private function exportExcel($logs, $stats)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit Validations');

        // Résumé
        $sheet->setCellValue('A1', 'RAPPORT D\'AUDIT — VALIDATIONS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('4F46E5');

        $sheet->setCellValue('A3', 'Total actions');
        $sheet->setCellValue('B3', $stats['total']);
        $sheet->setCellValue('A4', 'Approuvées');
        $sheet->setCellValue('B4', $stats['approved']);
        $sheet->getStyle('B4')->getFont()->getColor()->setRGB('166534');
        $sheet->setCellValue('A5', 'Refusées');
        $sheet->setCellValue('B5', $stats['rejected']);
        $sheet->getStyle('B5')->getFont()->getColor()->setRGB('991B1B');
        $sheet->setCellValue('A6', 'Commandes traitées');
        $sheet->setCellValue('B6', $stats['orders_touched']);
        $sheet->setCellValue('A7', 'Généré le');
        $sheet->setCellValue('B7', now()->format('d/m/Y H:i'));

        foreach (['A3', 'A4', 'A5', 'A6', 'A7'] as $cell) {
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // En-têtes table
        $headers = ['Date', 'Commande', 'Boutique', 'Demandeur', 'Montant (XOF)', 'Action', 'Niveau', 'Validateur', 'Commentaire'];
        $startRow = 9;
        foreach ($headers as $col => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $startRow;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4F46E5');
        }

        // Données
        foreach ($logs as $i => $log) {
            $row = $startRow + 1 + $i;
            $data = [
                \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i'),
                $log->purchaseOrder?->title ?? '—',
                $log->purchaseOrder?->boutique?->name ?? '—',
                $log->purchaseOrder?->user?->name ?? '—',
                (float) ($log->purchaseOrder?->amount ?? 0),
                $log->action === 'approved' ? 'Approuvée' : 'Refusée',
                ($log->validationLevel ? 'N' . $log->validationLevel->order . ' — ' . $log->validationLevel->name : '—'),
                $log->user?->name ?? '—',
                $log->comment ?? '',
            ];

            foreach ($data as $col => $value) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . $row;
                $sheet->setCellValue($cell, $value);
            }

            // Couleur action
            $actionCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6) . $row;
            $color = $log->action === 'approved' ? '6EE7B7' : 'FCA5A5';
            $sheet->getStyle($actionCell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($color);

            // Fond alterné
            if ($i % 2 === 0) {
                $rangeCell = 'A' . $row . ':I' . $row;
                $sheet->getStyle($rangeCell)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'audit-validations-' . now()->format('Y-m-d') . '.xlsx';
        $tmp      = tempnam(sys_get_temp_dir(), 'audit_');
        $writer->save($tmp);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
