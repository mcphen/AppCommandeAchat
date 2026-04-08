<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\ValidationLevel;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        }

        if ($user->canValidate()) {
            return $this->validatorDashboard($user);
        }

        return $this->demandeurDashboard($user);
    }

    private function adminDashboard(): Response
    {
        $stats = [
            'total'    => PurchaseOrder::count(),
            'pending'  => PurchaseOrder::where('status', 'pending')->count(),
            'approved' => PurchaseOrder::where('status', 'approved')->count(),
            'rejected' => PurchaseOrder::where('status', 'rejected')->count(),
        ];

        $recentOrders = PurchaseOrder::with('user')
            ->latest()
            ->limit(5)
            ->get();

        $totalUsers  = User::count();
        $totalLevels = ValidationLevel::count();

        $monthlyData = $this->getMonthlyData();

        return Inertia::render('Dashboard', [
            'stats'        => $stats,
            'recentOrders' => $recentOrders,
            'totalUsers'   => $totalUsers,
            'totalLevels'  => $totalLevels,
            'monthlyData'  => $monthlyData,
        ]);
    }

    private function validatorDashboard(User $user): Response
    {
        $levelOrder = $user->validationLevel?->order;

        $pendingCount = $levelOrder
            ? PurchaseOrder::where('status', 'pending')->where('current_level_order', $levelOrder)->count()
            : PurchaseOrder::where('status', 'pending')->count();

        $myValidations = $user->validationLogs()
            ->with('purchaseOrder')
            ->latest()
            ->limit(5)
            ->get();

        $stats = [
            'pending'     => $pendingCount,
            'my_approved' => $user->validationLogs()->where('action', 'approved')->count(),
            'my_rejected' => $user->validationLogs()->where('action', 'rejected')->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats'         => $stats,
            'myValidations' => $myValidations,
        ]);
    }

    private function demandeurDashboard(User $user): Response
    {
        $stats = [
            'total'    => $user->purchaseOrders()->count(),
            'draft'    => $user->purchaseOrders()->where('status', 'draft')->count(),
            'pending'  => $user->purchaseOrders()->where('status', 'pending')->count(),
            'approved' => $user->purchaseOrders()->where('status', 'approved')->count(),
            'rejected' => $user->purchaseOrders()->where('status', 'rejected')->count(),
        ];

        $recentOrders = $user->purchaseOrders()
            ->latest()
            ->limit(5)
            ->get();

        $monthlyData = $this->getMonthlyData($user->id);

        return Inertia::render('Dashboard', [
            'stats'        => $stats,
            'recentOrders' => $recentOrders,
            'monthlyData'  => $monthlyData,
        ]);
    }

    private function getMonthlyData(?int $userId = null): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        $query = PurchaseOrder::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->whereIn(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"), $months)
            ->groupBy('month', 'status');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $rows = $query->get()->groupBy('month');

        $labels  = [];
        $pending  = [];
        $approved = [];
        $rejected = [];
        $draft    = [];

        foreach ($months as $month) {
            $labels[]  = \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y');
            $group     = $rows->get($month, collect());
            $pending[]  = (int) $group->firstWhere('status', 'pending')?->count  ?? 0;
            $approved[] = (int) $group->firstWhere('status', 'approved')?->count ?? 0;
            $rejected[] = (int) $group->firstWhere('status', 'rejected')?->count ?? 0;
            $draft[]    = (int) $group->firstWhere('status', 'draft')?->count    ?? 0;
        }

        return compact('labels', 'pending', 'approved', 'rejected', 'draft');
    }
}
