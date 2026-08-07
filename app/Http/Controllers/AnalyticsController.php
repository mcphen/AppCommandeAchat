<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Analytics', [
            'blockedOrders'       => $this->getBlockedOrders(),
            'topFournisseurs'     => $this->getTopFournisseurs(),
            'topCategories'       => $this->getTopCategories(),
            'monthlyByBoutique'   => $this->getMonthlyByBoutique(),
            'deliveredByPeriod'   => $this->getDeliveredAmountByPeriod(),
            'fournisseurLeadTimes' => $this->getFournisseurLeadTimes(),
            'validationDelays'    => $this->getValidationDelays(),
            'rejectionRates'      => $this->getRejectionRates(),
        ]);
    }

    /**
     * Export Excel du détail des livraisons (lignes de réception) sur la période
     * affichée dans le graphique "Montant des commandes livrées" (mensuel ou trimestriel).
     */
    public function exportDelivered(Request $request)
    {
        $period = $request->query('period') === 'quarterly' ? 'quarterly' : 'monthly';

        [$from, $to] = $period === 'quarterly'
            ? [now()->startOfQuarter()->subQuarters(7), now()->endOfQuarter()]
            : [now()->startOfMonth()->subMonths(11), now()->endOfMonth()];

        $lines = DB::table('purchase_order_reception_lines as porl')
            ->join('purchase_order_receptions as por', 'porl.reception_id', '=', 'por.id')
            ->join('purchase_order_lines as pol', 'porl.purchase_order_line_id', '=', 'pol.id')
            ->join('purchase_orders as po', 'por.purchase_order_id', '=', 'po.id')
            ->leftJoin('articles as a', 'pol.article_id', '=', 'a.id')
            ->leftJoin('fournisseurs as f', 'pol.fournisseur_id', '=', 'f.id')
            ->leftJoin('boutiques as b', 'po.boutique_id', '=', 'b.id')
            ->whereBetween('por.received_at', [$from, $to])
            ->select(
                'por.received_at',
                'por.type as reception_type',
                'po.order_number',
                'po.title as order_title',
                'b.name as boutique_name',
                'f.name as fournisseur_name',
                'a.name as article_name',
                'a.reference as article_reference',
                'porl.quantity_received',
                'pol.unit_price',
                DB::raw('porl.quantity_received * pol.unit_price as subtotal')
            )
            ->orderBy('por.received_at')
            ->get();

        return $this->exportDeliveredExcel($lines, $period, $from, $to);
    }

    private function exportDeliveredExcel($lines, string $period, Carbon $from, Carbon $to)
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Commandes livrées');

        $sheet->setCellValue('A1', 'MONTANT DES COMMANDES LIVRÉES');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('4F46E5');

        $sheet->setCellValue('A3', 'Période');
        $sheet->setCellValue('B3', ($period === 'quarterly' ? 'Trimestrielle' : 'Mensuelle') . ' — du ' . $from->format('d/m/Y') . ' au ' . $to->format('d/m/Y'));
        $sheet->setCellValue('A4', 'Montant total livré');
        $sheet->setCellValue('B4', (float) $lines->sum('subtotal'));
        $sheet->setCellValue('A5', 'Généré le');
        $sheet->setCellValue('B5', now()->format('d/m/Y H:i'));

        foreach (['A3', 'A4', 'A5'] as $cell) {
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        $headers  = ['Date réception', 'Type', 'Commande', 'Boutique', 'Fournisseur', 'Article', 'Référence', 'Qté reçue', 'Prix unit. (XOF)', 'Sous-total (XOF)'];
        $startRow = 7;
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . $startRow;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('0EA5E9');
        }

        foreach ($lines as $i => $line) {
            $row  = $startRow + 1 + $i;
            $data = [
                Carbon::parse($line->received_at)->format('d/m/Y'),
                $line->reception_type === 'complete' ? 'Complète' : 'Partielle',
                $line->order_number ?? $line->order_title ?? '—',
                $line->boutique_name ?? '—',
                $line->fournisseur_name ?? '—',
                $line->article_name ?? '—',
                $line->article_reference ?? '—',
                (float) $line->quantity_received,
                (float) $line->unit_price,
                (float) $line->subtotal,
            ];

            foreach ($data as $col => $value) {
                $cell = Coordinate::stringFromColumnIndex($col + 1) . $row;
                $sheet->setCellValue($cell, $value);
            }

            if ($i % 2 === 0) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($spreadsheet);
        $filename = 'commandes-livrees-' . $period . '-' . now()->format('Y-m-d') . '.xlsx';
        $tmp      = tempnam(sys_get_temp_dir(), 'delivered_');
        $writer->save($tmp);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Délai moyen entre confirmation de la commande et réception complète, par fournisseur.
     * Repère les fournisseurs lents à livrer.
     */
    private function getFournisseurLeadTimes(): array
    {
        return DB::table('purchase_order_lines as pol')
            ->join('fournisseurs as f', 'pol.fournisseur_id', '=', 'f.id')
            ->join('purchase_orders as po', 'pol.purchase_order_id', '=', 'po.id')
            ->whereNotNull('po.ordered_at')
            ->whereNotNull('po.fully_received_at')
            ->select(
                'f.name',
                DB::raw('AVG(DATEDIFF(po.fully_received_at, po.ordered_at)) as avg_days'),
                DB::raw('COUNT(DISTINCT po.id) as orders_count')
            )
            ->groupBy('f.id', 'f.name')
            ->orderByDesc('avg_days')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'name'         => $r->name,
                'avg_days'     => round((float) $r->avg_days, 1),
                'orders_count' => (int) $r->orders_count,
            ])
            ->toArray();
    }

    private function getBlockedOrders(): array
    {
        return PurchaseOrder::with(['user', 'boutique'])
            ->where('status', 'pending')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<', now()->subDays(3))
            ->orderBy('submitted_at')
            ->get()
            ->map(fn ($o) => [
                'id'           => $o->id,
                'title'        => $o->title,
                'amount'       => (float) $o->amount,
                'submitted_at' => $o->submitted_at?->toISOString(),
                'days_waiting' => (int) now()->diffInDays($o->submitted_at),
                'user_name'    => $o->user->name,
                'boutique_name' => $o->boutique?->name ?? '—',
            ])
            ->toArray();
    }

    private function getTopFournisseurs(): array
    {
        return DB::table('purchase_order_lines as pol')
            ->join('fournisseurs as f', 'pol.fournisseur_id', '=', 'f.id')
            ->join('purchase_orders as po', 'pol.purchase_order_id', '=', 'po.id')
            ->where('po.status', 'approved')
            ->select(
                'f.name',
                DB::raw('SUM(pol.quantity * pol.unit_price) as total'),
                DB::raw('COUNT(DISTINCT po.id) as orders_count')
            )
            ->groupBy('f.id', 'f.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name'         => $r->name,
                'total'        => (float) $r->total,
                'orders_count' => (int) $r->orders_count,
            ])
            ->toArray();
    }

    private function getTopCategories(): array
    {
        return DB::table('purchase_order_lines as pol')
            ->join('articles as a', 'pol.article_id', '=', 'a.id')
            ->join('categories as c', 'a.category_id', '=', 'c.id')
            ->join('purchase_orders as po', 'pol.purchase_order_id', '=', 'po.id')
            ->where('po.status', 'approved')
            ->select(
                'c.name',
                DB::raw('SUM(pol.quantity * pol.unit_price) as total'),
                DB::raw('COUNT(DISTINCT po.id) as orders_count')
            )
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name'         => $r->name,
                'total'        => (float) $r->total,
                'orders_count' => (int) $r->orders_count,
            ])
            ->toArray();
    }

    private function getMonthlyByBoutique(): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $labels = array_map(
            fn ($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M Y'),
            $months
        );

        $rows = DB::table('purchase_orders as po')
            ->join('boutiques as b', 'po.boutique_id', '=', 'b.id')
            ->where('po.status', 'approved')
            ->whereIn(DB::raw("DATE_FORMAT(po.created_at, '%Y-%m')"), $months)
            ->select(
                'b.id',
                'b.name',
                DB::raw("DATE_FORMAT(po.created_at, '%Y-%m') as month"),
                DB::raw('SUM(po.amount) as total')
            )
            ->groupBy('b.id', 'b.name', 'month')
            ->get();

        $boutiquesMap = $rows->pluck('name', 'id')->unique();

        $palette = ['#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316'];
        $datasets = [];
        $i = 0;

        foreach ($boutiquesMap as $boutiqueId => $boutiqueName) {
            $data = [];
            foreach ($months as $month) {
                $row    = $rows->first(fn ($r) => $r->id == $boutiqueId && $r->month === $month);
                $data[] = $row ? (float) $row->total : 0;
            }
            $color      = $palette[$i % count($palette)];
            $datasets[] = [
                'label'           => $boutiqueName,
                'data'            => $data,
                'borderColor'     => $color,
                'backgroundColor' => $color . '22',
                'tension'         => 0.4,
                'fill'            => true,
                'pointRadius'     => 4,
                'pointHoverRadius' => 6,
            ];
            $i++;
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    /**
     * Montant réellement livré (reçu complètement ou partiellement), calculé
     * ligne par ligne (quantité reçue × prix unitaire) et daté de la réception,
     * pas de la commande — ainsi une livraison partielle n'est comptée qu'à
     * hauteur de ce qui a été effectivement réceptionné.
     */
    private function getDeliveredAmountByPeriod(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }
        $monthlyLabels = array_map(
            fn ($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M Y'),
            $months
        );

        $monthlyTotals = DB::table('purchase_order_reception_lines as porl')
            ->join('purchase_order_receptions as por', 'porl.reception_id', '=', 'por.id')
            ->join('purchase_order_lines as pol', 'porl.purchase_order_line_id', '=', 'pol.id')
            ->whereIn(DB::raw("DATE_FORMAT(por.received_at, '%Y-%m')"), $months)
            ->select(
                DB::raw("DATE_FORMAT(por.received_at, '%Y-%m') as period"),
                DB::raw('SUM(porl.quantity_received * pol.unit_price) as total')
            )
            ->groupBy('period')
            ->pluck('total', 'period');

        $monthlyData = array_map(fn ($m) => (float) ($monthlyTotals[$m] ?? 0), $months);

        $quarters = [];
        for ($i = 7; $i >= 0; $i--) {
            $q = now()->startOfQuarter()->subQuarters($i);
            $quarters[] = $q->year . '-Q' . $q->quarter;
        }
        $quarterlyLabels = array_map(fn ($q) => str_replace('-Q', ' T', $q), $quarters);

        $quarterlyTotals = DB::table('purchase_order_reception_lines as porl')
            ->join('purchase_order_receptions as por', 'porl.reception_id', '=', 'por.id')
            ->join('purchase_order_lines as pol', 'porl.purchase_order_line_id', '=', 'pol.id')
            ->select(
                DB::raw("CONCAT(YEAR(por.received_at), '-Q', QUARTER(por.received_at)) as period"),
                DB::raw('SUM(porl.quantity_received * pol.unit_price) as total')
            )
            ->groupBy('period')
            ->pluck('total', 'period');

        $quarterlyData = array_map(fn ($q) => (float) ($quarterlyTotals[$q] ?? 0), $quarters);

        return [
            'monthly'   => ['labels' => $monthlyLabels, 'data' => $monthlyData],
            'quarterly' => ['labels' => $quarterlyLabels, 'data' => $quarterlyData],
        ];
    }

    private function getValidationDelays(): array
    {
        $byLevel = DB::table('validation_logs as vl')
            ->join('purchase_orders as po', 'vl.purchase_order_id', '=', 'po.id')
            ->join('validation_levels as lvl', 'vl.validation_level_id', '=', 'lvl.id')
            ->whereNotNull('po.submitted_at')
            ->select(
                'lvl.name',
                'lvl.order',
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, po.submitted_at, vl.created_at)) as avg_hours'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('lvl.id', 'lvl.name', 'lvl.order')
            ->orderBy('lvl.order')
            ->get()
            ->map(fn ($r) => [
                'name'      => $r->name,
                'avg_hours' => round((float) $r->avg_hours, 1),
                'avg_days'  => round((float) $r->avg_hours / 24, 1),
                'total'     => (int) $r->total,
            ])
            ->toArray();

        $byValidator = DB::table('validation_logs as vl')
            ->join('purchase_orders as po', 'vl.purchase_order_id', '=', 'po.id')
            ->join('users as u', 'vl.user_id', '=', 'u.id')
            ->whereNotNull('po.submitted_at')
            ->select(
                'u.name',
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, po.submitted_at, vl.created_at)) as avg_hours'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN vl.action = "approved" THEN 1 ELSE 0 END) as approved'),
                DB::raw('SUM(CASE WHEN vl.action = "rejected" THEN 1 ELSE 0 END) as rejected')
            )
            ->groupBy('u.id', 'u.name')
            ->orderBy('avg_hours')
            ->get()
            ->map(fn ($r) => [
                'name'      => $r->name,
                'avg_hours' => round((float) $r->avg_hours, 1),
                'avg_days'  => round((float) $r->avg_hours / 24, 1),
                'total'     => (int) $r->total,
                'approved'  => (int) $r->approved,
                'rejected'  => (int) $r->rejected,
            ])
            ->toArray();

        return ['byLevel' => $byLevel, 'byValidator' => $byValidator];
    }

    private function getRejectionRates(): array
    {
        return DB::table('purchase_orders as po')
            ->join('users as u', 'po.user_id', '=', 'u.id')
            ->whereIn('po.status', ['approved', 'rejected'])
            ->select(
                'u.name',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN po.status = "rejected" THEN 1 ELSE 0 END) as rejected'),
                DB::raw('ROUND(SUM(CASE WHEN po.status = "rejected" THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as rate')
            )
            ->groupBy('u.id', 'u.name')
            ->having('total', '>=', 1)
            ->orderByDesc('rate')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'name'     => $r->name,
                'total'    => (int) $r->total,
                'rejected' => (int) $r->rejected,
                'approved' => (int) $r->total - (int) $r->rejected,
                'rate'     => (float) $r->rate,
            ])
            ->toArray();
    }
}
