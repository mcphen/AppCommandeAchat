<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Boutique;
use App\Models\Decaissement;
use App\Models\DisbursementRequest;
use App\Models\ModeReglement;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\DecaissementEnregistreNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DecaissementController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $this->buildIndexQuery($request, $user);

        $orders = $query->paginate(15)->withQueryString();

        $scopedBase = PurchaseOrder::where('status', 'approved')
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->where('boutique_id', $user->boutique_id));

        $stats = [
            'unpaid'         => (clone $scopedBase)->whereNull('payment_status')->count(),
            'partially_paid' => (clone $scopedBase)->where('payment_status', 'partially_paid')->count(),
            'paid'           => (clone $scopedBase)->where('payment_status', 'paid')->count(),
        ];

        return Inertia::render('Decaissements/Index', [
            'orders'    => $orders,
            'stats'     => $stats,
            'boutiques' => $user->isAdmin() ? Boutique::where('is_active', true)->orderBy('name')->get() : [],
            'filters'   => $this->getFilters($request),
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $orders = $this->buildIndexQuery($request, $request->user())
            ->with(['boutique', 'fournisseur', 'decaissements'])
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Decaissements BC');

        $headers = ['BC', 'Titre', 'Boutique', 'Fournisseur', 'Montant (XOF)', 'Décaissé (XOF)', 'Reste (XOF)', 'Statut paiement', 'Soumise le'];
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $statusLabels = ['unpaid' => 'Non décaissé', 'partially_paid' => 'Partiellement décaissé', 'paid' => 'Entièrement décaissé'];
        foreach ($orders as $row => $order) {
            $decaisse = $order->decaissements->sum('montant');
            $reste = max((float) $order->amount - (float) $decaisse, 0);
            $paymentStatus = $order->payment_status ?: 'unpaid';
            $values = [
                $order->order_number ?? '',
                $order->title,
                $order->boutique?->name ?? '',
                $order->fournisseur?->name ?? '',
                (float) $order->amount,
                (float) $decaisse,
                (float) $reste,
                $statusLabels[$paymentStatus] ?? $paymentStatus,
                $order->submitted_at?->format('d/m/Y') ?? '',
            ];
            foreach ($values as $col => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . ($row + 2), $value);
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        return response()->streamDownload(fn () => $writer->save('php://output'), 'decaissements-commandes-' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildIndexQuery(Request $request, $user)
    {
        $query = PurchaseOrder::where('status', 'approved')
            ->with(['boutique', 'fournisseur', 'decaissements'])
            ->withCount('decaissements')
            ->latest('submitted_at');

        if ($user->isCaissier() && $user->boutique_id) {
            $query->where('boutique_id', $user->boutique_id);
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('payment_status')) {
            $status = $request->string('payment_status')->toString();
            if ($status === 'unpaid') {
                $query->whereNull('payment_status');
            } else {
                $query->where('payment_status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
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
            'boutique_id'    => $request->string('boutique_id')->toString(),
            'payment_status' => $request->string('payment_status')->toString(),
            'search'         => $request->string('search')->toString(),
        ];
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isApproved(), 403, 'Cette commande n\'est pas approuvée.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($purchaseOrder->boutique_id === $user->boutique_id, 403, 'Accès non autorisé.');
        }

        $purchaseOrder->load(['boutique', 'fournisseur', 'user', 'lines.article', 'decaissements.recorder', 'decaissements.modeReglement']);

        return Inertia::render('Decaissements/Show', [
            'order'  => $purchaseOrder,
            'modes'  => ModeReglement::actifs(),
        ]);
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isApproved(), 403, 'Cette commande n\'est pas approuvée.');
        abort_unless($purchaseOrder->canBeDecaisse(), 403, 'Cette commande est déjà entièrement décaissée.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($purchaseOrder->boutique_id === $user->boutique_id, 403, 'Accès non autorisé.');
        }

        $resteADecaisser = $purchaseOrder->resteADecaisser();

        $validated = $request->validate([
            'montant'           => ['required', 'numeric', 'min:1', "max:{$resteADecaisser}"],
            'mode_reglement_id' => ['required', 'exists:modes_reglement,id'],
            'reference'         => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'decaissement_date' => ['required', 'date'],
        ]);

        $decaissement      = null;
        $dernierValidateur = null;

        DB::transaction(function () use ($validated, $purchaseOrder, $user, &$decaissement, &$dernierValidateur) {
            $decaissement = Decaissement::create([
                ...$validated,
                'purchase_order_id' => $purchaseOrder->id,
                'recorded_by'       => $user->id,
            ]);

            $totalDecaisse = (float) $purchaseOrder->decaissements()->sum('montant');
            $paymentStatus = $totalDecaisse >= (float) $purchaseOrder->amount ? 'paid' : 'partially_paid';

            $purchaseOrder->update(['payment_status' => $paymentStatus]);

            $dernierValidateur = $purchaseOrder->validationLogs()
                ->where('action', 'approved')
                ->latest()
                ->first()
                ?->user;
        });

        // Notifications envoyées hors transaction pour éviter un rollback si WhatsApp échoue
        try {
            if ($dernierValidateur) {
                $dernierValidateur->notify(new DecaissementEnregistreNotification($purchaseOrder, $decaissement));
            }

            User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))
                ->where('id', '!=', $dernierValidateur?->id)
                ->each(fn ($admin) => $admin->notify(new DecaissementEnregistreNotification($purchaseOrder, $decaissement)));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification décaissement échouée : ' . $e->getMessage());
        }

        return redirect()->route('decaissements.show', $purchaseOrder)
            ->with('success', 'Décaissement enregistré avec succès.');
    }

    public function downloadPdf(PurchaseOrder $purchaseOrder): HttpResponse
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isApproved(), 403);

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($purchaseOrder->boutique_id === $user->boutique_id, 403);
        }

        $purchaseOrder->load([
            'boutique',
            'fournisseur',
            'user',
            'decaissements.modeReglement',
            'decaissements.recorder',
        ]);

        $settings = AppSetting::allAsArray();

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

        $pdf = Pdf::loadView('pdf.decaissement', [
            'order'   => $purchaseOrder,
            'company' => $settings,
            'logoB64' => $logoBase64,
        ])->setPaper('a4', 'portrait');

        $filename = 'recu-decaissement-' . ($purchaseOrder->order_number ?? $purchaseOrder->id) . '.pdf';

        return $pdf->download($filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Interface caissier — Demandes de décaissement
    // ─────────────────────────────────────────────────────────────────────────

    public function indexDemandes(Request $request): Response
    {
        $user = $request->user();

        $query = DisbursementRequest::where('status', 'approved')
            ->with(['user', 'boutique', 'natureOperation', 'decaissement.modeReglement'])
            ->latest('updated_at');

        if ($user->isCaissier() && $user->boutique_id) {
            $query->where('boutique_id', $user->boutique_id);
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('payment_status')) {
            $ps = $request->string('payment_status')->toString();
            if ($ps === 'unpaid') {
                $query->whereNull('payment_status');
            } else {
                $query->where('payment_status', $ps);
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $demandes = $query->paginate(15)->withQueryString();

        $scopedBase = DisbursementRequest::where('status', 'approved')
            ->when($user->isCaissier() && $user->boutique_id, fn ($q) => $q->where('boutique_id', $user->boutique_id));

        $stats = [
            'unpaid' => (clone $scopedBase)->whereNull('payment_status')->count(),
            'paid'   => (clone $scopedBase)->where('payment_status', 'paid')->count(),
            'total'  => (clone $scopedBase)->count(),
        ];

        return Inertia::render('Decaissements/IndexDemandes', [
            'demandes'  => $demandes,
            'stats'     => $stats,
            'boutiques' => $user->isAdmin() ? Boutique::where('is_active', true)->orderBy('name')->get() : [],
            'filters'   => [
                'boutique_id'    => $request->string('boutique_id')->toString(),
                'payment_status' => $request->string('payment_status')->toString(),
                'search'         => $request->string('search')->toString(),
            ],
        ]);
    }

    public function showDemande(DisbursementRequest $disbursementRequest): Response
    {
        $user = auth()->user();

        abort_unless($disbursementRequest->isApproved(), 403, 'Cette demande n\'est pas approuvée.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($disbursementRequest->boutique_id === $user->boutique_id, 403, 'Accès non autorisé.');
        }

        $disbursementRequest->load([
            'user',
            'boutique',
            'natureOperation',
            'decaissement.modeReglement',
            'decaissement.recorder',
            'validationLogs.validationLevel',
            'validationLogs.user',
        ]);

        return Inertia::render('Decaissements/ShowDemande', [
            'demande' => $disbursementRequest,
            'modes'   => ModeReglement::actifs(),
        ]);
    }

    public function storeDemande(Request $request, DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($disbursementRequest->isApproved(), 403, 'Cette demande n\'est pas approuvée.');
        abort_unless(! $disbursementRequest->isPaid(), 403, 'Cette demande est déjà marquée comme payée.');

        if ($user->isCaissier() && $user->boutique_id) {
            abort_unless($disbursementRequest->boutique_id === $user->boutique_id, 403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'mode_reglement_id' => ['required', 'exists:modes_reglement,id'],
            'reference'         => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'decaissement_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($validated, $disbursementRequest, $user) {
            $decaissement = $disbursementRequest->decaissement;

            if ($decaissement) {
                // Mettre à jour le décaissement existant avec les infos de paiement réelles
                $decaissement->update([
                    'mode_reglement_id' => $validated['mode_reglement_id'],
                    'reference'         => $validated['reference'],
                    'notes'             => $validated['notes'],
                    'decaissement_date' => $validated['decaissement_date'],
                    'recorded_by'       => $user->id,
                ]);
            } else {
                // Sécurité : créer si non existant (ne devrait pas arriver)
                Decaissement::create([
                    'disbursement_request_id' => $disbursementRequest->id,
                    'purchase_order_id'       => null,
                    'montant'                 => $disbursementRequest->amount,
                    'mode_reglement_id'       => $validated['mode_reglement_id'],
                    'reference'               => $validated['reference'],
                    'notes'                   => $validated['notes'],
                    'decaissement_date'       => $validated['decaissement_date'],
                    'recorded_by'             => $user->id,
                ]);
            }

            $disbursementRequest->update(['payment_status' => 'paid']);
        });

        return redirect()->route('caissier.demandes.show', $disbursementRequest->uuid)
            ->with('success', 'Paiement enregistré avec succès.');
    }
}
