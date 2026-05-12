<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\AppSetting;
use App\Models\Article;
use App\Models\Boutique;
use App\Models\Fournisseur;
use App\Models\FournisseurArticle;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAttachment;
use App\Models\PurchaseOrderLine;
use App\Models\ValidationLevel;
use App\Notifications\OrderSubmittedNotification;
use App\Services\AccountingService;
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
        $fournisseurs = Fournisseur::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'is_approved']);

        return Inertia::render('PurchaseOrders/Create', [
            'boutique'     => auth()->user()?->boutique,
            'boutiques'    => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'city']),
            'articles'     => Article::with('category')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'reference', 'unit', 'unit_price', 'category_id']),
            'fournisseurs' => $fournisseurs,
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        // Boutique de l'utilisateur en priorité, sinon celle choisie dans le formulaire
        $boutique = $request->user()?->boutique
            ?? ($request->boutique_id ? Boutique::find($request->boutique_id) : null);

        $order = DB::transaction(function () use ($request, $boutique) {
            $lines  = collect($request->input('lines', []));
            $amount = $lines->isNotEmpty()
                ? $lines->sum(fn ($l) => (float) ($l['quantity'] ?? 1) * (float) ($l['unit_price'] ?? 0))
                : (float) ($request->amount ?? 0);

            $order = PurchaseOrder::create([
                'user_id'        => auth()->id(),
                'boutique_id'    => $boutique?->id,
                'fournisseur_id' => $request->fournisseur_id ?: null,
                'title'          => $request->title,
                'description'    => $request->description,
                'amount'         => $amount,
                'status'         => 'draft',
            ]);

            foreach ($lines as $line) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $order->id,
                    'article_id'        => $line['article_id'] ?? null,
                    'fournisseur_id'    => $line['fournisseur_id'] ?? null,
                    'quantity'          => $line['quantity'] ?? 1,
                    'unit_price'        => $line['unit_price'] ?? 0,
                    'note'              => $line['note'] ?? null,
                ]);
            }

            $this->storeAttachments($order, $request);

            return $order;
        });

        // Soumettre immédiatement si demandé
        if ($request->boolean('and_submit')) {
            $firstLevel = ValidationLevel::first_level();

            if ($firstLevel) {
                $order->update([
                    'status'              => 'pending',
                    'current_level_order' => $firstLevel->order,
                    'submitted_at'        => now(),
                ]);

                foreach ($firstLevel->validators as $validator) {
                    $validator->notify(new OrderSubmittedNotification($order, $firstLevel));
                }

                return redirect()->route('purchase-orders.show', $order)
                    ->with('success', 'Commande créée et soumise à validation.');
            }
        }

        return redirect()->route('purchase-orders.show', $order)
            ->with('success', 'Commande enregistrée en brouillon.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizeView($purchaseOrder);

        $purchaseOrder->load([
            'user',
            'boutique',
            'fournisseur',
            'attachments',
            'lines.article.category',
            'lines.fournisseur',
            'lines.receptionLines',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
            'receptions.receiver',
            'receptions.lines',
            'comments.user',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        // ─── Calcul des économies pour les commandes approuvées avec lignes ──
        $savings = null;
        $linesWithArticle = $purchaseOrder->lines->whereNotNull('article_id');

        if ($purchaseOrder->status === 'approved' && $linesWithArticle->isNotEmpty()) {
            $articleIds = $linesWithArticle->pluck('article_id')->unique();

            $catalogStats = FournisseurArticle::whereIn('article_id', $articleIds)
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('valide_jusqu_au')->orWhere('valide_jusqu_au', '>=', now()))
                ->selectRaw('article_id, MIN(unit_price) as best_price, AVG(unit_price) as avg_price, COUNT(*) as suppliers_count')
                ->groupBy('article_id')
                ->get()
                ->keyBy('article_id');

            $vsAverage   = 0;
            $vsBest      = 0;
            $linesWithData = 0;

            foreach ($linesWithArticle as $line) {
                if (! isset($catalogStats[$line->article_id])) continue;
                $stat  = $catalogStats[$line->article_id];
                $qty   = (float) $line->quantity;
                $actual = (float) $line->unit_price;

                $vsAverage += ($stat->avg_price - $actual) * $qty;
                $vsBest    += max(0, $actual - $stat->best_price) * $qty;
                $linesWithData++;
            }

            if ($linesWithData > 0) {
                $savings = [
                    'vs_average'         => (int) round($vsAverage),
                    'vs_best'            => (int) round($vsBest),
                    'lines_with_catalog' => $linesWithData,
                    'lines_total'        => $purchaseOrder->lines->count(),
                ];
            }
        }

        $user = auth()->user();

        return Inertia::render('PurchaseOrders/Show', [
            'order'     => $purchaseOrder,
            'levels'    => $levels,
            'savings'   => $savings,
            'canEdit'   => $purchaseOrder->isEditableBy($user),
            'canCancel' => $purchaseOrder->isCancellableBy($user),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        abort_unless($purchaseOrder->isEditableBy(auth()->user()), 403);

        $purchaseOrder->load(['attachments', 'boutique', 'fournisseur', 'lines.article', 'lines.fournisseur']);

        $fournisseurs = Fournisseur::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'is_approved']);

        return Inertia::render('PurchaseOrders/Edit', [
            'order'        => $purchaseOrder,
            'articles'     => Article::with('category')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'reference', 'unit', 'unit_price', 'category_id']),
            'fournisseurs' => $fournisseurs,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        DB::transaction(function () use ($request, $purchaseOrder) {
            $lines  = collect($request->input('lines', []));
            $amount = $lines->isNotEmpty()
                ? $lines->sum(fn ($l) => (float) ($l['quantity'] ?? 1) * (float) ($l['unit_price'] ?? 0))
                : (float) $request->amount;

            $purchaseOrder->update([
                'fournisseur_id' => $request->fournisseur_id ?: null,
                'title'          => $request->title,
                'description'    => $request->description,
                'amount'         => $amount,
            ]);

            // Sync des lignes : supprimer les anciennes, recréer
            $purchaseOrder->lines()->delete();
            foreach ($lines as $line) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'article_id'        => $line['article_id'] ?? null,
                    'fournisseur_id'    => $line['fournisseur_id'] ?? null,
                    'quantity'          => $line['quantity'] ?? 1,
                    'unit_price'        => $line['unit_price'] ?? 0,
                    'note'              => $line['note'] ?? null,
                ]);
            }

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

            // Si la commande était en attente de validation au premier niveau, on la remet en brouillon
            // pour qu'elle soit soumise à nouveau après modification.
            if ($purchaseOrder->isPending()) {
                $purchaseOrder->update([
                    'status'              => 'draft',
                    'current_level_order' => null,
                    'submitted_at'        => null,
                ]);
            }
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Commande mise à jour. Veuillez la soumettre à nouveau pour validation.');
    }

    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless(
            $purchaseOrder->user_id === auth()->id() && in_array($purchaseOrder->status, ['draft', 'rejected', 'needs_revision']),
            403
        );

        // En révision : on reprend au niveau qui a demandé la révision
        // Sinon : on repart du premier niveau
        $targetLevelOrder = $purchaseOrder->isNeedsRevision()
            ? $purchaseOrder->current_level_order
            : null;

        $firstLevel = $targetLevelOrder
            ? ValidationLevel::where('order', $targetLevelOrder)->first()
            : ValidationLevel::first_level();

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

    public function markOrdered(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($purchaseOrder->canBeOrdered(), 422, 'Cette commande ne peut pas être confirmée.');

        $orderNumber = DB::transaction(function () use ($purchaseOrder) {
            $year   = now()->year;
            $prefix = "BC-{$year}-";
            $last   = PurchaseOrder::where('order_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->max('order_number');
            $seq    = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
            $number = $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);

            $purchaseOrder->update([
                'order_number'    => $number,
                'delivery_status' => 'ordered',
                'ordered_at'      => now(),
            ]);

            return $number;
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', "Commande confirmée. Numéro officiel : {$orderNumber}");
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder): HttpResponse
    {
        $this->authorizeView($purchaseOrder);

        $purchaseOrder->load([
            'user',
            'boutique',
            'fournisseur',
            'attachments',
            'lines.article.category',
            'lines.fournisseur',
            'validationLogs.validationLevel',
            'validationLogs.user',
        ]);

        $levels   = ValidationLevel::orderBy('order')->get();
        $settings = AppSetting::allAsArray();

        // Logo en base64 pour DomPDF
        $logoBase64 = null;
        if (! empty($settings['company_logo'])) {
            $absPath = storage_path('app/public/' . $settings['company_logo']);
            if (file_exists($absPath)) {
                $mime       = mime_content_type($absPath);
                $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
            }
        }
        if (! $logoBase64) {
            $fallback = public_path('logo_scn.jpg');
            if (file_exists($fallback)) {
                $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($fallback));
            }
        }

        $pdf = Pdf::loadView('pdf.purchase_order', [
            'order'    => $purchaseOrder,
            'levels'   => $levels,
            'company'  => $settings,
            'logoB64'  => $logoBase64,
        ])->setPaper('a4', 'portrait');

        $filename = 'commande-' . $purchaseOrder->id . '-' . str($purchaseOrder->title)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    public function cancel(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->isCancellableBy(auth()->user()), 403);

        $purchaseOrder->update([
            'status'              => 'cancelled',
            'current_level_order' => null,
        ]);

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Commande annulée.');
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
