<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FournisseurController extends Controller
{
    public function index(): Response
    {
        $fournisseurs = Fournisseur::withCount('orderLines')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Fournisseurs/Index', [
            'fournisseurs' => $fournisseurs,
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

        return Inertia::render('Admin/Fournisseurs/Show', [
            'fournisseur' => $fournisseur,
            'orders'      => $orders,
            'stats'       => $stats,
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
