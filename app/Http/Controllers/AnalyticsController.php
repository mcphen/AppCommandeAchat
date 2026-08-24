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
            'topProjects'         => $this->getTopProjects(),
            'purchasesByProject'   => $this->getPurchasesByProjectPeriod(),
            'approvedByPeriod'    => $this->getApprovedAmountByPeriod(),
            'deliveredByPeriod'   => $this->getDeliveredAmountByPeriod(),
            'fournisseurLeadTimes' => $this->getFournisseurLeadTimes(),
            'validationDelays'    => $this->getValidationDelays(),
            'rejectionRates'      => $this->getRejectionRates(),
        ]);
    }

    public function projects(Request $request): Response
    {
        $dateFrom = $request->date('date_from')?->startOfDay() ?? now()->subMonths(11)->startOfMonth();
        $dateTo = $request->date('date_to')?->endOfDay() ?? now()->endOfMonth();
        $groupBy = in_array($request->string('group_by')->toString(), ['monthly', 'quarterly', 'annual'], true)
            ? $request->string('group_by')->toString()
            : 'monthly';

        $base = DB::table('purchase_orders as po')
            ->join('projects as p', 'po.project_id', '=', 'p.id')
            ->where('po.status', 'approved')
            ->whereBetween(DB::raw('COALESCE(po.order_date, DATE(po.created_at))'), [$dateFrom->toDateString(), $dateTo->toDateString()]);

        if ($request->filled('project_id')) {
            $base->where('po.project_id', $request->integer('project_id'));
        }

        $total = (float) (clone $base)->sum('po.amount');
        $ordersCount = (int) (clone $base)->distinct()->count('po.id');
        $projects = (clone $base)
            ->select('p.id', 'p.code', 'p.name', DB::raw('SUM(po.amount) as total'), DB::raw('COUNT(po.id) as orders_count'), DB::raw('AVG(po.amount) as average'))
            ->groupBy('p.id', 'p.code', 'p.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'total' => (float) $row->total,
                'orders_count' => (int) $row->orders_count,
                'average' => (float) $row->average,
                'share' => $total > 0 ? round((float) $row->total * 100 / $total, 1) : 0,
            ]);

        $periodExpression = match ($groupBy) {
            'quarterly' => 'CONCAT(YEAR(COALESCE(po.order_date, po.created_at)), \'-Q\', QUARTER(COALESCE(po.order_date, po.created_at)))',
            'annual' => 'CAST(YEAR(COALESCE(po.order_date, po.created_at)) AS CHAR)',
            default => 'DATE_FORMAT(COALESCE(po.order_date, po.created_at), \'%Y-%m\')',
        };
        $evolution = (clone $base)
            ->select(DB::raw($periodExpression.' as period'), DB::raw('SUM(po.amount) as total'), DB::raw('COUNT(po.id) as orders_count'))
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => ['period' => $row->period, 'total' => (float) $row->total, 'orders_count' => (int) $row->orders_count]);

        $tranches = [
            ['key' => 'under_1m', 'label' => 'Moins de 1 M', 'min' => 0, 'max' => 1000000],
            ['key' => '1m_5m', 'label' => '1 à 5 M', 'min' => 1000000, 'max' => 5000000],
            ['key' => '5m_20m', 'label' => '5 à 20 M', 'min' => 5000000, 'max' => 20000000],
            ['key' => 'over_20m', 'label' => 'Plus de 20 M', 'min' => 20000000, 'max' => null],
        ];
        $trancheData = collect($tranches)->map(function ($tranche) use ($base) {
            $query = (clone $base)->where('po.amount', '>=', $tranche['min']);
            $tranche['max'] === null ? $query : $query->where('po.amount', '<', $tranche['max']);
            return [
                'key' => $tranche['key'],
                'label' => $tranche['label'],
                'total' => (float) $query->sum('po.amount'),
                'orders_count' => (int) $query->count('po.id'),
            ];
        });

        $topSuppliers = DB::table('purchase_order_lines as pol')
            ->join('purchase_orders as po', 'pol.purchase_order_id', '=', 'po.id')
            ->join('fournisseurs as f', 'pol.fournisseur_id', '=', 'f.id')
            ->where('po.status', 'approved')
            ->whereBetween(DB::raw('COALESCE(po.order_date, DATE(po.created_at))'), [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->when($request->filled('project_id'), fn ($query) => $query->where('po.project_id', $request->integer('project_id')))
            ->select('f.name', DB::raw('SUM(pol.quantity * pol.unit_price) as total'), DB::raw('COUNT(DISTINCT po.id) as orders_count'))
            ->groupBy('f.id', 'f.name')->orderByDesc('total')->limit(10)->get();

        $orders = (clone $base)
            ->select('po.id', 'po.title', 'po.order_number', 'po.order_date', 'po.created_at', 'po.amount', 'po.amount_ttc', 'p.name as project_name')
            ->orderByDesc(DB::raw('COALESCE(po.order_date, po.created_at)'))
            ->paginate(15)->withQueryString();

        return Inertia::render('Analytics/Projects', [
            'filters' => ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString(), 'group_by' => $groupBy, 'project_id' => $request->string('project_id')->toString()],
            'projectOptions' => DB::table('projects')->orderBy('name')->get(['id', 'code', 'name']),
            'summary' => ['total' => $total, 'orders_count' => $ordersCount, 'projects_count' => $projects->count(), 'average' => $ordersCount > 0 ? $total / $ordersCount : 0],
            'projects' => $projects,
            'evolution' => $evolution,
            'tranches' => $trancheData,
            'topSuppliers' => $topSuppliers,
            'orders' => $orders,
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
        return PurchaseOrder::with('user')
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

    private function getTopProjects(): array
    {
        $grandTotal = (float) DB::table('purchase_orders')->where('status', 'approved')->whereNotNull('project_id')->sum('amount');

        return DB::table('purchase_orders as po')
            ->join('projects as p', 'po.project_id', '=', 'p.id')
            ->where('po.status', 'approved')
            ->select(
                'p.id',
                'p.code',
                'p.name',
                DB::raw('SUM(po.amount) as total'),
                DB::raw('COUNT(po.id) as orders_count')
            )
            ->groupBy('p.id', 'p.code', 'p.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id'           => (int) $r->id,
                'code'         => $r->code,
                'name'         => $r->name,
                'total'        => (float) $r->total,
                'orders_count' => (int) $r->orders_count,
                'share'        => $grandTotal > 0 ? round((float) $r->total * 100 / $grandTotal, 1) : 0,
            ])
            ->toArray();
    }

    private function getApprovedAmountByPeriod(): array
    {
        $definitions = [
            'monthly' => [
                'keys' => collect(range(11, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m')),
                'expression' => 'DATE_FORMAT(COALESCE(order_date, created_at), \'%Y-%m\')',
                'label' => fn ($key) => Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y'),
            ],
            'quarterly' => [
                'keys' => collect(range(7, 0))->map(fn ($i) => tap(now()->startOfQuarter()->subQuarters($i), fn () => null))->map(fn ($date) => $date->year.'-Q'.$date->quarter),
                'expression' => 'CONCAT(YEAR(COALESCE(order_date, created_at)), \'-Q\', QUARTER(COALESCE(order_date, created_at)))',
                'label' => fn ($key) => str_replace('-Q', ' T', $key),
            ],
            'annual' => [
                'keys' => collect(range(4, 0))->map(fn ($i) => (string) now()->subYears($i)->year),
                'expression' => 'CAST(YEAR(COALESCE(order_date, created_at)) AS CHAR)',
                'label' => fn ($key) => $key,
            ],
        ];

        return collect($definitions)->map(function ($definition) {
            $totals = DB::table('purchase_orders')
                ->where('status', 'approved')
                ->select(DB::raw($definition['expression'].' as period'), DB::raw('SUM(amount) as total'))
                ->groupBy('period')
                ->pluck('total', 'period');

            return [
                'labels' => $definition['keys']->map($definition['label'])->values()->all(),
                'data' => $definition['keys']->map(fn ($key) => (float) ($totals[$key] ?? 0))->values()->all(),
            ];
        })->all();
    }

    private function getPurchasesByProjectPeriod(): array
    {
        $periods = [
            'monthly' => ['keys' => collect(range(11, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m')), 'sql' => 'DATE_FORMAT(COALESCE(po.order_date, po.created_at), \'%Y-%m\')', 'label' => fn ($key) => Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y')],
            'quarterly' => ['keys' => collect(range(7, 0))->map(function ($i) { $date = now()->startOfQuarter()->subQuarters($i); return $date->year.'-Q'.$date->quarter; }), 'sql' => 'CONCAT(YEAR(COALESCE(po.order_date, po.created_at)), \'-Q\', QUARTER(COALESCE(po.order_date, po.created_at)))', 'label' => fn ($key) => str_replace('-Q', ' T', $key)],
            'annual' => ['keys' => collect(range(4, 0))->map(fn ($i) => (string) now()->subYears($i)->year), 'sql' => 'CAST(YEAR(COALESCE(po.order_date, po.created_at)) AS CHAR)', 'label' => fn ($key) => $key],
        ];
        $colors = ['#6366f1', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6'];

        return collect($periods)->map(function ($period) use ($colors) {
            $projectIds = DB::table('purchase_orders as po')
                ->where('po.status', 'approved')->whereNotNull('po.project_id')
                ->whereIn(DB::raw($period['sql']), $period['keys']->all())
                ->select('po.project_id', DB::raw('SUM(po.amount) as total'))
                ->groupBy('po.project_id')->orderByDesc('total')->limit(5)->pluck('po.project_id');

            $rows = DB::table('purchase_orders as po')->join('projects as p', 'po.project_id', '=', 'p.id')
                ->where('po.status', 'approved')->whereIn('po.project_id', $projectIds)
                ->whereIn(DB::raw($period['sql']), $period['keys']->all())
                ->select('p.id', 'p.name', DB::raw($period['sql'].' as period'), DB::raw('SUM(po.amount) as total'))
                ->groupBy('p.id', 'p.name', 'period')->get();

            $projects = $rows->groupBy('id')->sortByDesc(fn ($items) => $items->sum('total'));
            $datasets = $projects->values()->map(function ($items, $index) use ($period, $colors) {
                $color = $colors[$index % count($colors)];
                return [
                    'label' => $items->first()->name,
                    'data' => $period['keys']->map(fn ($key) => (float) (optional($items->firstWhere('period', $key))->total ?? 0))->all(),
                    'borderColor' => $color, 'backgroundColor' => $color.'22',
                    'tension' => 0.35, 'fill' => false, 'pointRadius' => 4, 'pointHoverRadius' => 6,
                ];
            })->all();

            return ['labels' => $period['keys']->map($period['label'])->all(), 'datasets' => $datasets];
        })->all();
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
