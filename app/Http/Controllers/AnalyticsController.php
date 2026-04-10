<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Analytics', [
            'blockedOrders'     => $this->getBlockedOrders(),
            'topFournisseurs'   => $this->getTopFournisseurs(),
            'topCategories'     => $this->getTopCategories(),
            'monthlyByBoutique' => $this->getMonthlyByBoutique(),
            'validationDelays'  => $this->getValidationDelays(),
            'rejectionRates'    => $this->getRejectionRates(),
        ]);
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
