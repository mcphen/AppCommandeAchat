<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Boutique;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAttachment;
use App\Models\ValidationLevel;
use App\Notifications\OrderSubmittedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user  = $request->user();
        $query = $this->buildQuery($request, $user);

        $orders = $query->paginate(10)->withQueryString();

        return Inertia::render('PurchaseOrders/Index', [
            'orders'      => $orders,
            'boutiques'   => Boutique::where('is_active', true)->orderBy('name')->get(),
            'demandeurs'  => $user->isAdmin() ? \App\Models\User::whereHas('role', fn ($q) => $q->where('slug', 'demandeur'))->orderBy('name')->get(['id', 'name']) : [],
            'levels'      => ValidationLevel::orderBy('order')->get(['id', 'name', 'order']),
            'levelsCount' => ValidationLevel::count(),
            'filters'     => $this->getFilters($request),
        ]);
    }

    public function export(Request $request, string $format): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(in_array($format, ['csv', 'excel', 'pdf']), 404);

        $user   = $request->user();
        $orders = $this->buildQuery($request, $user)
            ->with(['boutique', 'user', 'validationLogs.user', 'validationLogs.validationLevel'])
            ->get();

        $levels = ValidationLevel::orderBy('order')->get();

        return match ($format) {
            'csv'   => $this->exportCsv($orders),
            'excel' => $this->exportExcel($orders),
            'pdf'   => $this->exportPdf($orders, $levels),
        };
    }

    private function buildQuery(Request $request, $user)
    {
        $query = PurchaseOrder::with(['attachments', 'boutique', 'user', 'validationLogs.user', 'validationLogs.validationLevel'])->latest();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('user_id') && $user->isAdmin()) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->number('amount_min'));
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->number('amount_max'));
        }

        if ($request->filled('level_order')) {
            $query->where('status', 'pending')
                  ->where('current_level_order', $request->integer('level_order'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function getFilters(Request $request): array
    {
        return [
            'boutique_id' => $request->string('boutique_id')->toString(),
            'status'      => $request->string('status')->toString(),
            'user_id'     => $request->string('user_id')->toString(),
            'date_from'   => $request->string('date_from')->toString(),
            'date_to'     => $request->string('date_to')->toString(),
            'amount_min'  => $request->string('amount_min')->toString(),
            'amount_max'  => $request->string('amount_max')->toString(),
            'level_order' => $request->string('level_order')->toString(),
            'search'      => $request->string('search')->toString(),
        ];
    }

    private function exportCsv($orders): \Symfony\Component\HttpFoundation\Response
    {
        $statusLabels = ['draft' => 'Brouillon', 'pending' => 'En attente', 'approved' => 'Approuvee', 'rejected' => 'Refusee'];

        $csv  = "\xEF\xBB\xBF"; // BOM UTF-8
        $csv .= "ID;Titre;Boutique;Demandeur;Montant (XOF);Statut;Date creation;Soumise le\n";

        foreach ($orders as $order) {
            $csv .= implode(';', [
                $order->id,
                '"' . str_replace('"', '""', $order->title) . '"',
                '"' . ($order->boutique?->name ?? '') . '"',
                '"' . ($order->user?->name ?? '') . '"',
                number_format($order->amount, 0, ',', ' '),
                $statusLabels[$order->status] ?? $order->status,
                $order->created_at->format('d/m/Y'),
                $order->submitted_at?->format('d/m/Y') ?? '',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="commandes-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    private function exportExcel($orders): \Symfony\Component\HttpFoundation\Response
    {
        $statusLabels = ['draft' => 'Brouillon', 'pending' => 'En attente', 'approved' => 'Approuvee', 'rejected' => 'Refusee'];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Commandes');

        // En-têtes
        $headers = ['ID', 'Titre', 'Boutique', 'Demandeur', 'Montant (XOF)', 'Statut', 'Date creation', 'Soumise le', 'Validateurs'];
        foreach ($headers as $col => $header) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4F46E5');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        // Données
        foreach ($orders as $row => $order) {
            $validators = $order->validationLogs
                ->where('action', 'approved')
                ->map(fn ($log) => ($log->validationLevel?->name ?? '') . ': ' . ($log->user?->name ?? ''))
                ->join(' | ');

            $rowData = [
                $order->id,
                $order->title,
                $order->boutique?->name ?? '',
                $order->user?->name ?? '',
                (float) $order->amount,
                $statusLabels[$order->status] ?? $order->status,
                $order->created_at->format('d/m/Y'),
                $order->submitted_at?->format('d/m/Y') ?? '',
                $validators,
            ];

            foreach ($rowData as $col => $value) {
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1) . ($row + 2);
                $sheet->setCellValue($cell, $value);
            }

            // Couleur statut
            $statusColors = ['draft' => 'CBD5E1', 'pending' => 'FDE68A', 'approved' => '6EE7B7', 'rejected' => 'FCA5A5'];
            $statusCol    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(6) . ($row + 2);
            $color        = $statusColors[$order->status] ?? 'FFFFFF';
            $sheet->getStyle($statusCol)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB($color);
        }

        // Auto-largeur colonnes
        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'commandes-' . now()->format('Y-m-d') . '.xlsx';
        $tmp      = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tmp);

        return response()->download($tmp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function exportPdf($orders, $levels): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = Pdf::loadView('pdf.purchase_orders_list', [
            'orders' => $orders,
            'levels' => $levels,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('commandes-' . now()->format('Y-m-d') . '.pdf');
    }

    public function create(): Response
    {
        return Inertia::render('PurchaseOrders/Create', [
            'boutique' => auth()->user()?->boutique,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $boutique = $request->user()?->boutique;

        if (! $boutique) {
            return back()->with('error', 'Votre compte n\'est rattachÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â© ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â  aucune boutique.');
        }

        DB::transaction(function () use ($request, $boutique) {
            $order = PurchaseOrder::create([
                'user_id'     => auth()->id(),
                'boutique_id' => $boutique->id,
                'title'       => $request->title,
                'description' => $request->description,
                'amount'      => $request->amount,
                'status'      => 'draft',
            ]);

            $this->storeAttachments($order, $request);
        });

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Commande créée avec succès.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizeView($purchaseOrder);

        $purchaseOrder->load([
            'boutique',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('PurchaseOrders/Show', [
            'order'  => $purchaseOrder,
            'levels' => $levels,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        abort_unless($purchaseOrder->isEditableBy(auth()->user()), 403);

        $purchaseOrder->load(['attachments', 'boutique']);

        return Inertia::render('PurchaseOrders/Edit', [
            'order' => $purchaseOrder,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        DB::transaction(function () use ($request, $purchaseOrder) {
            $purchaseOrder->update([
                'title'       => $request->title,
                'description' => $request->description,
                'amount'      => $request->amount,
            ]);

            // Supprimer les pièces jointes marquées pour suppression
            if ($request->deleted_attachment_ids) {
                $toDelete = $purchaseOrder->attachments()
                    ->whereIn('id', $request->deleted_attachment_ids)
                    ->get();

                foreach ($toDelete as $attachment) {
                    Storage::disk('private')->delete($attachment->file_path);
                    $attachment->delete();
                }
            }

            $this->storeAttachments($purchaseOrder, $request);
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Commande mise à jour.');
    }

    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless(
            $purchaseOrder->user_id === auth()->id() && in_array($purchaseOrder->status, ['draft', 'rejected']),
            403
        );

        $firstLevel = ValidationLevel::first_level();

        abort_if(! $firstLevel, 422, 'Aucun niveau de validation configuré.');

        $purchaseOrder->update([
            'status'              => 'pending',
            'current_level_order' => $firstLevel->order,
            'submitted_at'        => now(),
        ]);

        // Notifier les validateurs du premier niveau
        $validators = $firstLevel->validators;
        foreach ($validators as $validator) {
            $validator->notify(new OrderSubmittedNotification($purchaseOrder, $firstLevel));
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Commande soumise à validation.');
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder): HttpResponse
    {
        $this->authorizeView($purchaseOrder);

        $purchaseOrder->load([
            'user',
            'boutique',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        $pdf = Pdf::loadView('pdf.purchase_order', [
            'order'  => $purchaseOrder,
            'levels' => $levels,
        ])->setPaper('a4', 'portrait');

        $filename = 'commande-' . $purchaseOrder->id . '-' . str($purchaseOrder->title)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->isEditableBy(auth()->user()), 403);

        foreach ($purchaseOrder->attachments as $attachment) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Commande supprimée.');
    }

    private function storeAttachments(PurchaseOrder $order, $request): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store("attachments/{$order->id}", 'private');

            PurchaseOrderAttachment::create([
                'purchase_order_id' => $order->id,
                'file_path'         => $path,
                'file_name'         => $file->getClientOriginalName(),
                'file_size'         => $file->getSize(),
            ]);
        }
    }

    private function authorizeView(PurchaseOrder $order): void
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->canValidate()) {
            return;
        }

        abort_unless($order->user_id === $user->id, 403);
    }
}
