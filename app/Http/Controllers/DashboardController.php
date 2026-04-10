<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Boutique;
use App\Models\Budget;
use App\Models\Category;
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

        return $this->demandeurDashboard($user);
    }

    private function adminDashboard(): Response
    {
        $budgetService = app(BudgetService::class);
        $stats = [
            'total'           => PurchaseOrder::count(),
            'pending'         => PurchaseOrder::where('status', 'pending')->count(),
            'approved'        => PurchaseOrder::where('status', 'approved')->count(),
            'rejected'        => PurchaseOrder::where('status', 'rejected')->count(),
            'budget_approved' => (int) PurchaseOrder::where('status', 'approved')->sum('amount'),
            'budget_pending'  => (int) PurchaseOrder::where('status', 'pending')->sum('amount'),
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

        $pendingCount = $levelOrder
            ? PurchaseOrder::where('status', 'pending')->where('current_level_order', $levelOrder)->count()
            : PurchaseOrder::where('status', 'pending')->count();

        $stats = [
            'pending'     => $pendingCount,
            'my_approved' => $user->validationLogs()->where('action', 'approved')->count(),
            'my_rejected' => $user->validationLogs()->where('action', 'rejected')->count(),
        ];

        $recentOrders = PurchaseOrder::with(['user', 'boutique'])
            ->where('status', 'pending')
            ->when($levelOrder, fn ($q) => $q->where('current_level_order', $levelOrder))
            ->latest()
            ->limit(8)
            ->get();

        $totalLevels = ValidationLevel::count();

        $activeDelegations = $user->activeDelegationsReceived()
            ->with(['delegator', 'validationLevel'])
            ->get();

        return Inertia::render('Dashboard', [
            'stats'             => $stats,
            'recentOrders'      => $recentOrders,
            'validationLevel'   => $user->validationLevel,
            'totalLevels'       => $totalLevels,
            'activeDelegations' => $activeDelegations,
        ]);
    }

    private function demandeurDashboard(User $user): Response
    {
        $stats = [
            'total'           => $user->purchaseOrders()->count(),
            'draft'           => $user->purchaseOrders()->where('status', 'draft')->count(),
            'pending'         => $user->purchaseOrders()->where('status', 'pending')->count(),
            'approved'        => $user->purchaseOrders()->where('status', 'approved')->count(),
            'rejected'        => $user->purchaseOrders()->where('status', 'rejected')->count(),
            'budget_approved' => (int) $user->purchaseOrders()->where('status', 'approved')->sum('amount'),
            'budget_pending'  => (int) $user->purchaseOrders()->where('status', 'pending')->sum('amount'),
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
