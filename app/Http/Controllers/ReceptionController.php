<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReception;
use App\Models\PurchaseOrderReceptionLine;
use App\Models\Boutique;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReceptionController extends Controller
{
    public function index(Request $request): Response
    {
        $user  = auth()->user();
        $query = $this->buildIndexQuery($request, $user);

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('Receptions/Index', [
            'orders'    => $orders,
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters'   => $this->getFilters($request),
            'stats' => [
                'ordered'            => $this->countForUser($user, 'ordered'),
                'partially_received' => $this->countForUser($user, 'partially_received'),
                'received'           => $this->countForUser($user, 'received'),
            ],
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $orders = $this->buildIndexQuery($request, $request->user())->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Receptions');

        $headers = ['BC', 'Titre', 'Boutique', 'Fournisseur', 'Montant (XOF)', 'Statut livraison', 'Progression', 'Commandée le', 'Clôturée le'];
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $statusLabels = ['ordered' => 'Commandée', 'partially_received' => 'Reçue partiellement', 'received' => 'Reçue entièrement'];
        foreach ($orders as $row => $order) {
            $lines = $order->lines ?? collect();
            $orderedQty = $lines->sum(fn ($line) => (float) $line->quantity);
            $receivedQty = $lines->sum(fn ($line) => (float) ($line->quantity_received_total ?? 0));
            $progress = $orderedQty > 0 ? min(100, round(($receivedQty / $orderedQty) * 100)) : ($order->delivery_status === 'received' ? 100 : 0);
            $values = [
                $order->order_number ?? '',
                $order->title,
                $order->boutique?->name ?? '',
                $order->fournisseur?->name ?? '',
                (float) $order->amount,
                $statusLabels[$order->delivery_status] ?? $order->delivery_status,
                $progress . '%',
                $order->ordered_at?->format('d/m/Y') ?? '',
                $order->fully_received_at?->format('d/m/Y') ?? '',
            ];
            foreach ($values as $col => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . ($row + 2), $value);
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(fn () => $writer->save('php://output'), 'receptions-livraisons-' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildIndexQuery(Request $request, $user)
    {
        $query = PurchaseOrder::with([
                'boutique',
                'fournisseur',
                'user',
                'lines.receptionLines',
                'receptions.receiver',
            ])
            ->whereNotNull('delivery_status')
            ->latest('ordered_at');

        if (! $user->isAdmin()) {
            if ($user->isDemandeur() && $user->boutique_id) {
                $boutiqueUserIds = \App\Models\User::where('boutique_id', $user->boutique_id)->pluck('id');
                $query->whereIn('user_id', $boutiqueUserIds);
            } else {
                $query->where('user_id', $user->id);
            }
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->string('delivery_status'));
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function getFilters(Request $request): array
    {
        return [
            'delivery_status' => $request->string('delivery_status')->toString(),
            'boutique_id'     => $request->string('boutique_id')->toString(),
            'search'          => $request->string('search')->toString(),
        ];
    }

    private function countForUser($user, string $status): int
    {
        $q = PurchaseOrder::where('delivery_status', $status);
        if (! $user->isAdmin()) {
            if ($user->isDemandeur() && $user->boutique_id) {
                $boutiqueUserIds = \App\Models\User::where('boutique_id', $user->boutique_id)->pluck('id');
                $q->whereIn('user_id', $boutiqueUserIds);
            } else {
                $q->where('user_id', $user->id);
            }
        }
        return $q->count();
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $user = auth()->user();

        // Seul l'admin ou le créateur de la commande peut saisir une réception
        abort_unless($user->isAdmin() || $purchaseOrder->user_id === $user->id, 403);

        // La commande doit être en phase de livraison
        abort_unless($purchaseOrder->canReceive(), 422, 'Cette commande ne peut pas encore recevoir de livraison.');

        $validated = $request->validate([
            'received_at'    => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date'   => ['nullable', 'date'],
            'invoice_amount' => ['nullable', 'numeric', 'min:0'],
            'lines'          => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required', 'integer', 'exists:purchase_order_lines,id'],
            'lines.*.quantity_received'      => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder, $user) {
            $reception = PurchaseOrderReception::create([
                'purchase_order_id' => $purchaseOrder->id,
                'received_by'       => $user->id,
                'received_at'       => $validated['received_at'],
                'notes'             => $validated['notes'] ?? null,
                'invoice_number'    => $validated['invoice_number'] ?? null,
                'invoice_date'      => $validated['invoice_date'] ?? null,
                'invoice_amount'    => $validated['invoice_amount'] ?? null,
                'type'              => 'partial', // recalculé après
            ]);

            foreach ($validated['lines'] as $line) {
                if ((float) $line['quantity_received'] > 0) {
                    PurchaseOrderReceptionLine::create([
                        'reception_id'           => $reception->id,
                        'purchase_order_line_id' => $line['purchase_order_line_id'],
                        'quantity_received'      => $line['quantity_received'],
                    ]);
                }
            }

            $this->refreshDeliveryStatus($purchaseOrder, $reception);
        });

        // Génération des écritures comptables à la réception (hors transaction)
        try {
            $reception->load(['lines.orderLine.article.category', 'purchaseOrder.fournisseur']);
            app(AccountingService::class)->generateForReception($reception);
        } catch (\Throwable) {
            // Ne pas bloquer la réception si la génération comptable échoue
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Réception enregistrée avec succès.');
    }

    /**
     * Rattacher / corriger les informations de facture sur une réception existante.
     * Accessible par l'admin et le créateur de la commande.
     */
    public function updateInvoice(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderReception $reception): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $purchaseOrder->user_id === $user->id, 403);
        abort_unless($reception->purchase_order_id === $purchaseOrder->id, 404);

        $validated = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date'   => ['nullable', 'date'],
            'invoice_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $reception->update($validated);

        // Recalcul des écritures comptables avec le montant facture mis à jour
        try {
            $reception->load(['lines.orderLine.article.category', 'purchaseOrder.fournisseur']);
            app(AccountingService::class)->generateForReception($reception);
        } catch (\Throwable) {
            //
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Informations de facture mises à jour.');
    }

    private function refreshDeliveryStatus(PurchaseOrder $purchaseOrder, PurchaseOrderReception $reception): void
    {
        $lines = $purchaseOrder->lines()->get();

        if ($lines->isEmpty()) {
            $purchaseOrder->update(['delivery_status' => 'received', 'fully_received_at' => now()]);
            $reception->update(['type' => 'complete']);
            return;
        }

        $allReceived = true;

        foreach ($lines as $line) {
            $totalReceived = PurchaseOrderReceptionLine::whereHas(
                'reception',
                fn ($q) => $q->where('purchase_order_id', $purchaseOrder->id)
            )
                ->where('purchase_order_line_id', $line->id)
                ->sum('quantity_received');

            if ((float) $totalReceived < (float) $line->quantity) {
                $allReceived = false;
                break;
            }
        }

        $purchaseOrder->update([
            'delivery_status'   => $allReceived ? 'received' : 'partially_received',
            'fully_received_at' => $allReceived ? now() : null,
        ]);

        $reception->update(['type' => $allReceived ? 'complete' : 'partial']);
    }
}
