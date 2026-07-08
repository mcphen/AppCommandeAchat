<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FournisseurController extends Controller
{
    /** Colonnes autorisées pour le tri, mappées vers leur expression SQL. */
    private const SORTABLE = [
        'name'                => 'name',
        'code'                => 'code',
        'city'                => 'city',
        'is_approved'         => 'is_approved',
        'is_active'           => 'is_active',
        'order_lines_count'   => 'order_lines_count',
        'total_achats_valide' => 'total_achats_valide',
    ];

    private function filteredFournisseurs(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $search = $request->string('search')->toString();
        $sort = $request->string('sort', 'name')->toString();
        $direction = $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $sortColumn = self::SORTABLE[$sort] ?? 'name';

        return Fournisseur::withCount('orderLines')
            ->withSum(
                ['orderLines as total_achats_valide' => fn ($q) => $q->whereHas(
                    'purchaseOrder',
                    fn ($q2) => $q2->where('status', 'approved')
                )],
                \Illuminate\Support\Facades\DB::raw('quantity * unit_price')
            )
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            }))
            ->when(
                in_array($sortColumn, ['total_achats_valide', 'order_lines_count'], true),
                fn ($q) => $q->orderByRaw("COALESCE({$sortColumn}, 0) {$direction}"),
                fn ($q) => $q->orderBy($sortColumn, $direction)
            );
    }

    public function index(Request $request): Response
    {
        $fournisseurs = $this->filteredFournisseurs($request)
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'    => Fournisseur::count(),
            'approved' => Fournisseur::where('is_approved', true)->count(),
            'pending'  => Fournisseur::where('is_approved', false)->where('is_active', true)->count(),
            'inactive' => Fournisseur::where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/Fournisseurs/Index', [
            'fournisseurs' => $fournisseurs,
            'filters'      => [
                'search'    => $request->string('search')->toString(),
                'sort'      => $request->string('sort', 'name')->toString(),
                'direction' => $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc',
            ],
            'stats' => $stats,
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $fournisseurs = $this->filteredFournisseurs($request)->get();

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Nom', 'Code', 'Email', 'Telephone', 'Ville', 'Homologue', 'Actif', 'Lignes de commande', 'Montant achats valides'], ';');

        foreach ($fournisseurs as $f) {
            fputcsv($output, [
                $f->name,
                $f->code,
                $f->email ?? '',
                $f->phone ?? '',
                $f->city ?? '',
                $f->is_approved ? 'Oui' : 'Non',
                $f->is_active ? 'Oui' : 'Non',
                $f->order_lines_count ?? 0,
                number_format((float) ($f->total_achats_valide ?? 0), 2, ',', ''),
            ], ';');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        $filename = 'fournisseurs-' . now()->format('Y-m-d') . '.csv';

        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function dashboard(): Response
    {
        $approvedLines = fn ($q) => $q->whereHas('purchaseOrder', fn ($q2) => $q2->where('status', 'approved'));

        $totalBudget = (float) (DB::table('purchase_order_lines')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->where('purchase_orders.status', 'approved')
            ->sum(DB::raw('purchase_order_lines.quantity * purchase_order_lines.unit_price')));

        $topBudget = Fournisseur::withSum(['orderLines as total_achats_valide' => $approvedLines], DB::raw('quantity * unit_price'))
            ->orderByDesc('total_achats_valide')
            ->limit(10)
            ->get()
            ->map(fn ($f) => [
                'id'      => $f->id,
                'name'    => $f->name,
                'code'    => $f->code,
                'total'   => (float) ($f->total_achats_valide ?? 0),
                'percent' => $totalBudget > 0 ? round((float) ($f->total_achats_valide ?? 0) / $totalBudget * 100, 1) : 0,
            ]);

        $concentrationTop5 = $totalBudget > 0
            ? round($topBudget->take(5)->sum('total') / $totalBudget * 100, 1)
            : 0;

        $since = now()->subMonths(11)->startOfMonth();
        $monthlyRaw = DB::table('purchase_order_lines')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->where('purchase_orders.status', 'approved')
            ->where(DB::raw('COALESCE(purchase_orders.order_date, purchase_orders.created_at)'), '>=', $since)
            ->selectRaw("DATE_FORMAT(COALESCE(purchase_orders.order_date, purchase_orders.created_at), '%Y-%m') as month, SUM(purchase_order_lines.quantity * purchase_order_lines.unit_price) as total")
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $monthlyEvolution = collect(range(0, 11))->map(function (int $i) use ($monthlyRaw) {
            $month = now()->subMonths(11 - $i)->format('Y-m');

            return [
                'month' => $month,
                'label' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M Y'),
                'total' => (float) ($monthlyRaw[$month]->total ?? 0),
            ];
        });

        $riskSuppliers = Fournisseur::withCount('orderLines')
            ->withSum(['orderLines as total_achats_valide' => $approvedLines], DB::raw('quantity * unit_price'))
            ->where('is_approved', false)
            ->where('is_active', true)
            ->orderByDesc('order_lines_count')
            ->limit(10)
            ->get()
            ->map(fn ($f) => [
                'id'                => $f->id,
                'name'              => $f->name,
                'code'              => $f->code,
                'order_lines_count' => $f->order_lines_count,
                'total'             => (float) ($f->total_achats_valide ?? 0),
            ]);

        return Inertia::render('Admin/Fournisseurs/Dashboard', [
            'totalBudget'       => $totalBudget,
            'concentrationTop5' => $concentrationTop5,
            'topBudget'         => $topBudget,
            'monthlyEvolution'  => $monthlyEvolution,
            'riskSuppliers'     => $riskSuppliers,
        ]);
    }

    public function show(Fournisseur $fournisseur): Response
    {
        // Commandes liées directement (fournisseur au niveau commande)
        // + commandes liées via les lignes — union dédoublonnée
        $orderIds = PurchaseOrder::where('fournisseur_id', $fournisseur->id)
            ->pluck('id')
            ->merge(
                PurchaseOrder::whereHas(
                    'lines',
                    fn ($q) => $q->where('fournisseur_id', $fournisseur->id)
                )->pluck('id')
            )
            ->unique();

        $orders = PurchaseOrder::with(['user', 'boutique'])
            ->withSum(
                ['lines as lines_amount' => fn ($q) => $q->where('fournisseur_id', $fournisseur->id)],
                \Illuminate\Support\Facades\DB::raw('quantity * unit_price')
            )
            ->whereIn('id', $orderIds)
            ->latest()
            ->get();

        $stats = [
            'total'           => $orders->count(),
            'approved'        => $orders->where('status', 'approved')->count(),
            'pending'         => $orders->where('status', 'pending')->count(),
            'rejected'        => $orders->where('status', 'rejected')->count(),
            'budget_approved' => (int) $orders->where('status', 'approved')->sum('amount'),
            'budget_pending'  => (int) $orders->where('status', 'pending')->sum('amount'),
        ];

        $articles = Article::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Fournisseurs/Show', [
            'fournisseur' => $fournisseur,
            'orders'      => $orders,
            'stats'       => $stats,
            'articles'    => $articles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Fournisseurs/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:fournisseurs,code',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'is_active'   => 'boolean',
            'is_approved' => 'boolean',
        ]);

        Fournisseur::create($data);

        return redirect()->route('admin.fournisseurs.index')
            ->with('success', 'Fournisseur créé avec succès.');
    }

    public function edit(Fournisseur $fournisseur): Response
    {
        return Inertia::render('Admin/Fournisseurs/Form', [
            'fournisseur' => $fournisseur,
        ]);
    }

    public function update(Request $request, Fournisseur $fournisseur): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:fournisseurs,code,' . $fournisseur->id,
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'is_active'   => 'boolean',
            'is_approved' => 'boolean',
        ]);

        $fournisseur->update($data);

        return redirect()->route('admin.fournisseurs.index')
            ->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Fournisseur $fournisseur): RedirectResponse
    {
        abort_if($fournisseur->orderLines()->exists(), 422, 'Ce fournisseur est utilisé dans des lignes de commande.');

        $fournisseur->delete();

        return redirect()->route('admin.fournisseurs.index')
            ->with('success', 'Fournisseur supprimé.');
    }
}
