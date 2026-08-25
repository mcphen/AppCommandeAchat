<?php
namespace App\Http\Controllers;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReception;
use App\Models\PurchaseOrderReceptionLine;
use App\Models\ReceptionTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ReceptionTransferController extends Controller {

    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $visibleOrders = fn ($query) => $query
            ->where('status', 'approved')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id));

        $query = ReceptionTransfer::with([
            'project',
            'actor',
            'reception.purchaseOrder.fournisseur',
            'reception.purchaseOrder.boutique',
            'lines.receptionLine.orderLine.article',
        ])->whereHas('reception.purchaseOrder', $visibleOrders);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transferred_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transferred_at', '<=', $request->date('date_to'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('project', fn ($project) => $project->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('reception.purchaseOrder', fn ($order) => $order->where('title', 'like', "%{$search}%")->orWhere('order_number', 'like', "%{$search}%"));
            });
        }

        $statsQuery = ReceptionTransfer::whereHas('reception.purchaseOrder', $visibleOrders);
        $lineStatsQuery = \App\Models\ReceptionTransferLine::whereHas('transfer.reception.purchaseOrder', $visibleOrders);
        $availableLines = PurchaseOrderReceptionLine::withSum('transferLines as transferred_total', 'quantity_transferred')
            ->whereHas('reception.purchaseOrder', $visibleOrders)
            ->get();


        $availableReceptions = PurchaseOrderReception::with([
            'purchaseOrder:id,title,order_number,user_id,status',
            'lines.orderLine.article',
            'lines.transferLines',
        ])
            ->whereHas('purchaseOrder', $visibleOrders)
            ->latest('received_at')
            ->get()
            ->map(function ($reception) {
                $lines = $reception->lines->map(function ($line) {
                    $transferred = (float) $line->transferLines->sum('quantity_transferred');
                    $available = max(0, (float) $line->quantity_received - $transferred);
                    return [
                        'id' => $line->id,
                        'label' => $line->orderLine?->article?->name ?? "Ligne #{$line->purchase_order_line_id}",
                        'unit' => $line->orderLine?->article?->unit ?? '',
                        'quantity_received' => (float) $line->quantity_received,
                        'quantity_transferred' => $transferred,
                        'quantity_available' => $available,
                    ];
                })->filter(fn ($line) => $line['quantity_available'] > 0)->values();

                return [
                    'id' => $reception->id,
                    'received_at' => $reception->received_at,
                    'purchase_order' => $reception->purchaseOrder,
                    'lines' => $lines,
                ];
            })
            ->filter(fn ($reception) => $reception['lines']->isNotEmpty())
            ->values();

        return \Inertia\Inertia::render('Transfers/Index', [
            'transfers' => $query->latest('transferred_at')->paginate(15)->withQueryString(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'availableReceptions' => $availableReceptions,
            'filters' => [
                'project_id' => $request->string('project_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'search' => $request->string('search')->toString(),
            ],
            'stats' => [
                'transfers' => (clone $statsQuery)->count(),
                'projects' => (clone $statsQuery)->distinct()->count('project_id'),
                'transferred_quantity' => (float) $lineStatsQuery->sum('quantity_transferred'),
                'pending_quantity' => $availableLines->sum(fn ($line) => max(0, (float) $line->quantity_received - (float) ($line->transferred_total ?? 0))),
            ],
        ]);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderReception $reception): RedirectResponse {
        $user = $request->user();
        abort_unless($user->isAdmin() || $purchaseOrder->user_id === $user->id, 403);
        abort_unless($purchaseOrder->status === 'approved', 422, 'Le bon de commande doit être entièrement approuvé.');
        abort_unless($reception->purchase_order_id === $purchaseOrder->id, 404);
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'transferred_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.reception_line_id' => ['required', 'integer', 'distinct'],
            'lines.*.quantity_transferred' => ['required', 'numeric', 'gt:0'],
        ]);
        abort_unless(Project::whereKey($data['project_id'])->where('is_active', true)->exists(), 422, 'Le chantier sélectionné est inactif.');
        DB::transaction(function () use ($data, $reception, $user) {
            $ids = collect($data['lines'])->pluck('reception_line_id');
            $lines = PurchaseOrderReceptionLine::where('reception_id', $reception->id)->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            if ($lines->count() !== $ids->count()) throw ValidationException::withMessages(['lines' => 'Une ligne ne fait pas partie de cette réception.']);
            foreach ($data['lines'] as $index => $input) {
                $line = $lines[$input['reception_line_id']];
                $available = (float) $line->quantity_received - (float) $line->transferLines()->sum('quantity_transferred');
                if ((float) $input['quantity_transferred'] > $available) throw ValidationException::withMessages(["lines.{$index}.quantity_transferred" => "Quantité disponible : {$available}."]);
            }
            $transfer = ReceptionTransfer::create([
                'reception_id' => $reception->id, 'project_id' => $data['project_id'], 'transferred_by' => $user->id,
                'transferred_at' => $data['transferred_at'], 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['lines'] as $input) $transfer->lines()->create($input);
        });
        return back()->with('success', 'Transfert vers le chantier enregistré.');
    }
}