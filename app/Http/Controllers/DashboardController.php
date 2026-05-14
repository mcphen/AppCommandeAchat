<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Boutique;
use App\Models\Budget;
use App\Models\Category;
use App\Models\DisbursementRequest;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\ValidationLevel;
use App\Services\BudgetService;
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

        if ($user->isCaissier()) {
            return $this->caissierDashboard($user);
        }

        if ($user->isAgent()) {
            return redirect()->route('mon-compte.index');
        }

        return $this->demandeurDashboard($user);
    }

    private function adminDashboard(): Response
    {
        $budgetService = app(BudgetService::class);
        $stats = [
            'total'            => PurchaseOrder::count(),
            'pending'          => PurchaseOrder::where('status', 'pending')->count(),
            'approved'         => PurchaseOrder::where('status', 'approved')->count(),
            'rejected'         => PurchaseOrder::where('status', 'rejected')->count(),
            'budget_approved'  => (int) PurchaseOrder::where('status', 'approved')->sum('amount'),
            'budget_pending'   => (int) PurchaseOrder::where('status', 'pending')->sum('amount'),
            // Demandes de décaissement
            'dd_total'         => DisbursementRequest::count(),
            'dd_pending'       => DisbursementRequest::where('status', 'pending')->count(),
            'dd_approved'      => DisbursementRequest::where('status', 'approved')->count(),
            'dd_rejected'      => DisbursementRequest::where('status', 'rejected')->count(),
            'dd_budget_approved' => (int) DisbursementRequest::where('status', 'approved')->sum('amount'),
            'dd_budget_pending'  => (int) DisbursementRequest::where('status', 'pending')->sum('amount'),
        ];

        $recentOrders = PurchaseOrder::with(['user', 'boutique'])
            ->latest()
            ->limit(8)
            ->get();

        $totalUsers    = User::count();
        $totalLevels   = ValidationLevel::count();
        $totalBoutiques = Boutique::where('is_active', true)->count();

        $boutiqueStats = Boutique::withCount([
                'purchaseOrders as orders_total',
                'purchaseOrders as orders_approved' => fn ($q) => $q->where('status', 'approved'),
                'purchaseOrders as orders_pending'  => fn ($q) => $q->where('status', 'pending'),
            ])
            ->withSum(['purchaseOrders as budget_approved' => fn ($q) => $q->where('status', 'approved')], 'amount')
            ->where('is_active', true)
            ->orderByDesc('orders_total')
            ->get();

        $monthlyData = $this->getMonthlyData();

        $alertBudgets = $budgetService->getAlertBudgets(now()->year, 5);

        return Inertia::render('Dashboard', [
            'stats'          => $stats,
            'recentOrders'   => $recentOrders,
            'totalUsers'     => $totalUsers,
            'totalLevels'    => $totalLevels,
            'totalBoutiques' => $totalBoutiques,
            'boutiqueStats'  => $boutiqueStats,
            'monthlyData'    => $monthlyData,
            'alertBudgets'   => $alertBudgets,
            'checklist'      => $this->buildChecklist(auth()->user()),
        ]);
    }

    private function validatorDashboard(User $user): Response
    {
        $levelOrder = $user->validationLevel?->order;

        $pendingQuery = PurchaseOrder::where('status', 'pending')
            ->when($levelOrder, fn ($q) => $q->where('current_level_order', $levelOrder));

        $myApprovedLogs = $user->validationLogs()->where('action', 'approved');

        $ddPendingLevel = DisbursementRequest::where('status', 'pending')
            ->when($levelOrder, fn ($q) => $q->where('current_level_order', $levelOrder))
            ->count();

        $stats = [
            'pending'               => (clone $pendingQuery)->count(),
            'budget_pending'        => (int) (clone $pendingQuery)->sum('amount'),
            'my_approved'           => $user->validationLogs()->where('action', 'approved')->count(),
            'my_rejected'           => $user->validationLogs()->where('action', 'rejected')->count(),
            'my_budget_approved'    => (int) $myApprovedLogs
                ->join('purchase_orders', 'validation_logs.purchase_order_id', '=', 'purchase_orders.id')
                ->sum('purchase_orders.amount'),
            'global_approved'       => PurchaseOrder::where('status', 'approved')->count(),
            'global_rejected'       => PurchaseOrder::where('status', 'rejected')->count(),
            'global_budget_approved'=> (int) PurchaseOrder::where('status', 'approved')->sum('amount'),
            'global_pending'        => PurchaseOrder::where('status', 'pending')->count(),
            // Demandes de décaissement
            'dd_pending_my_level'   => $ddPendingLevel,
            'dd_pending'            => DisbursementRequest::where('status', 'pending')->count(),
            'dd_approved'           => DisbursementRequest::where('status', 'approved')->count(),
        ];

        $recentOrders = PurchaseOrder::with(['user', 'boutique'])
            ->where('status', 'pending')
            ->when($levelOrder, fn ($q) => $q->where('current_level_order', $levelOrder))
            ->latest()
            ->limit(8)
            ->get();

        // Pipeline : état de chaque niveau de validation
        $allLevels = ValidationLevel::orderBy('order')->get();
        $pipeline  = $allLevels->map(fn ($lvl) => [
            'level_order' => $lvl->order,
            'level_name'  => $lvl->name,
            'pending'     => PurchaseOrder::where('status', 'pending')->where('current_level_order', $lvl->order)->count(),
            'amount'      => (int) PurchaseOrder::where('status', 'pending')->where('current_level_order', $lvl->order)->sum('amount'),
        ])->values()->all();

        $totalLevels = count($pipeline);

        $activeDelegations = $user->activeDelegationsReceived()
            ->with(['delegator', 'validationLevel'])
            ->get();

        return Inertia::render('Dashboard', [
            'stats'             => $stats,
            'recentOrders'      => $recentOrders,
            'validationLevel'   => $user->validationLevel,
            'totalLevels'       => $totalLevels,
            'activeDelegations' => $activeDelegations,
            'pipeline'          => $pipeline,
        ]);
    }

    private function demandeurDashboard(User $user): Response
    {
        $stats = [
            'total'              => $user->purchaseOrders()->count(),
            'draft'              => $user->purchaseOrders()->where('status', 'draft')->count(),
            'pending'            => $user->purchaseOrders()->where('status', 'pending')->count(),
            'approved'           => $user->purchaseOrders()->where('status', 'approved')->count(),
            'rejected'           => $user->purchaseOrders()->where('status', 'rejected')->count(),
            'budget_approved'    => (int) $user->purchaseOrders()->where('status', 'approved')->sum('amount'),
            'budget_pending'     => (int) $user->purchaseOrders()->where('status', 'pending')->sum('amount'),
            // Demandes de décaissement
            'dd_total'           => $user->disbursementRequests()->count(),
            'dd_pending'         => $user->disbursementRequests()->where('status', 'pending')->count(),
            'dd_approved'        => $user->disbursementRequests()->where('status', 'approved')->count(),
            'dd_budget_approved' => (int) $user->disbursementRequests()->where('status', 'approved')->sum('amount'),
        ];

        $recentOrders = $user->purchaseOrders()
            ->with('boutique')
            ->latest()
            ->limit(8)
            ->get();

        $monthlyData = $this->getMonthlyData($user->id);
        $totalLevels = ValidationLevel::count();

        return Inertia::render('Dashboard', [
            'stats'        => $stats,
            'recentOrders' => $recentOrders,
            'monthlyData'  => $monthlyData,
            'boutique'     => $user->boutique,
            'totalLevels'  => $totalLevels,
        ]);
    }

    private function caissierDashboard(User $user): Response
    {
        $boutiqueId = $user->boutique_id;

        $baseQuery = PurchaseOrder::where('status', 'approved')
            ->when($boutiqueId, fn ($q) => $q->where('boutique_id', $boutiqueId));

        $ddBase = DisbursementRequest::where('status', 'approved')
            ->when($boutiqueId, fn ($q) => $q->where('boutique_id', $boutiqueId));

        $stats = [
            'approved_total'  => (clone $baseQuery)->count(),
            'unpaid'          => (clone $baseQuery)->whereNull('payment_status')->count(),
            'partially_paid'  => (clone $baseQuery)->where('payment_status', 'partially_paid')->count(),
            'paid'            => (clone $baseQuery)->where('payment_status', 'paid')->count(),
            'total_to_pay'    => (int) (clone $baseQuery)->whereNull('payment_status')->sum('amount'),
            'total_paid_bc'   => (int) (clone $baseQuery)->where('payment_status', 'paid')->sum('amount'),
            // Demandes de décaissement
            'dd_total'        => (clone $ddBase)->count(),
            'dd_unpaid'       => (clone $ddBase)->whereNull('payment_status')->count(),
            'dd_paid'         => (clone $ddBase)->where('payment_status', 'paid')->count(),
            'dd_to_pay'       => (int) (clone $ddBase)->whereNull('payment_status')->sum('amount'),
            'dd_paid_amount'  => (int) (clone $ddBase)->where('payment_status', 'paid')->sum('amount'),
        ];

        // Bons de commande non payés à traiter
        $recentOrders = (clone $baseQuery)
            ->whereNull('payment_status')
            ->with(['boutique', 'fournisseur'])
            ->latest()
            ->limit(5)
            ->get();

        // Demandes de décaissement non payées à traiter
        $recentDisbursements = (clone $ddBase)
            ->whereNull('payment_status')
            ->with(['boutique', 'natureOperation'])
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats'               => $stats,
            'recentOrders'        => $recentOrders,
            'recentDisbursements' => $recentDisbursements,
            'boutique'            => $user->boutique,
        ]);
    }

    private function buildChecklist(User $user): ?array
    {
        // Ne pas afficher si l'admin a fermé définitivement la checklist
        if ($user->checklist_dismissed_at !== null) {
            return null;
        }

        $hasBoutique        = Boutique::where('is_active', true)->exists();
        $hasValidationLevel = ValidationLevel::exists();
        $hasOtherUser       = User::where('id', '!=', $user->id)->exists();
        $hasBudget          = class_exists(Budget::class) && Budget::exists();
        $hasArticle         = Article::where('is_active', true)->exists();
        $hasFournisseur     = Fournisseur::where('is_approved', true)->exists();

        $steps = [
            [
                'key'       => 'boutique',
                'label'     => 'Créer une boutique',
                'detail'    => 'Définissez votre premier point de vente ou département.',
                'done'      => $hasBoutique,
                'href'      => '/admin/boutiques/create',
                'cta'       => 'Créer une boutique',
            ],
            [
                'key'       => 'validation_level',
                'label'     => 'Configurer le circuit de validation',
                'detail'    => 'Définissez au moins un niveau d\'approbation pour les commandes.',
                'done'      => $hasValidationLevel,
                'href'      => '/admin/validation-levels/create',
                'cta'       => 'Ajouter un niveau',
            ],
            [
                'key'       => 'invite_user',
                'label'     => 'Inviter un utilisateur',
                'detail'    => 'Ajoutez un demandeur ou un validateur à votre équipe.',
                'done'      => $hasOtherUser,
                'href'      => '/admin/users/create',
                'cta'       => 'Ajouter un utilisateur',
            ],
            [
                'key'       => 'fournisseur',
                'label'     => 'Approuver un fournisseur',
                'detail'    => 'Ajoutez et approuvez au moins un fournisseur dans le référentiel.',
                'done'      => $hasFournisseur,
                'href'      => '/admin/fournisseurs/create',
                'cta'       => 'Ajouter un fournisseur',
            ],
            [
                'key'       => 'article',
                'label'     => 'Créer un article dans le catalogue',
                'detail'    => 'Les demandeurs sélectionneront leurs articles depuis ce catalogue.',
                'done'      => $hasArticle,
                'href'      => '/admin/articles/create',
                'cta'       => 'Ajouter un article',
            ],
            [
                'key'       => 'budget',
                'label'     => 'Définir un budget',
                'detail'    => 'Configurez une enveloppe budgétaire pour contrôler les dépenses.',
                'done'      => $hasBudget,
                'href'      => '/admin/budgets/create',
                'cta'       => 'Créer un budget',
            ],
        ];

        $completed = collect($steps)->where('done', true)->count();
        $total     = count($steps);
        $allDone   = $completed === $total;

        return [
            'steps'     => $steps,
            'completed' => $completed,
            'total'     => $total,
            'all_done'  => $allDone,
        ];
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

        $labels   = [];
        $pending  = [];
        $approved = [];
        $rejected = [];
        $draft    = [];

        foreach ($months as $month) {
            $labels[]   = \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y');
            $group      = $rows->get($month, collect());
            $pending[]  = (int) ($group->firstWhere('status', 'pending')?->count  ?? 0);
            $approved[] = (int) ($group->firstWhere('status', 'approved')?->count ?? 0);
            $rejected[] = (int) ($group->firstWhere('status', 'rejected')?->count ?? 0);
            $draft[]    = (int) ($group->firstWhere('status', 'draft')?->count    ?? 0);
        }

        return compact('labels', 'pending', 'approved', 'rejected', 'draft');
    }
}
