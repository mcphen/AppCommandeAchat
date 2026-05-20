<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectValidationRequest;
use App\Models\Boutique;
use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Models\ValidationLog;
use App\Notifications\DecaissementPretNotification;
use App\Notifications\OrderApprovedAtLevelNotification;
use App\Notifications\OrderFinallyApprovedNotification;
use App\Notifications\OrderRejectedNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ValidationController extends Controller
{
    public function history(Request $request): Response
    {
        $user = $request->user();
        $query = $this->buildHistoryQuery($request, $user);

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('Validations/History', [
            'orders'   => $orders,
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters'  => [
                'status'      => $request->string('status')->toString(),
                'boutique_id' => $request->string('boutique_id')->toString(),
                'date_from'   => $request->string('date_from')->toString(),
                'date_to'     => $request->string('date_to')->toString(),
            ],
        ]);
    }

    public function exportIndex(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $orders = $this->buildIndexQuery($request, $request->user())->get();
        return $this->exportOrdersExcel($orders, 'validations-en-attente');
    }

    public function exportHistory(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $orders = $this->buildHistoryQuery($request, $request->user())->get();
        return $this->exportOrdersExcel($orders, 'historique-validations');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $this->buildIndexQuery($request, $user);

        $orders = $query->paginate(10)->withQueryString();

        return Inertia::render('Validations/Index', [
            'orders' => $orders,
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(),
            'levelsCount' => ValidationLevel::count(),
            'filters' => [
                'boutique_id' => $request->string('boutique_id')->toString(),
            ],
        ]);
    }

    private function buildIndexQuery(Request $request, $user)
    {
        $query = PurchaseOrder::where('status', 'pending')
            ->with(['user', 'boutique', 'attachments'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            $validatableOrders = $user->validatableLevelOrders();
            abort_unless(count($validatableOrders) > 0, 403, 'Vous n\'avez aucun niveau de validation actif.');
            $query->whereIn('current_level_order', $validatableOrders);
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        return $query;
    }

    private function buildHistoryQuery(Request $request, $user)
    {
        $query = PurchaseOrder::with(['user', 'boutique', 'validationLogs.validationLevel'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            $levelOrders = $user->validatableLevelOrders();
            abort_unless(count($levelOrders) > 0, 403, 'Vous n\'avez aucun niveau de validation actif.');

            $levelIds = ValidationLevel::whereIn('order', $levelOrders)->pluck('id');

            $query->where(function ($q) use ($levelOrders, $levelIds) {
                $q->where(fn ($sub) => $sub->where('status', 'pending')->whereIn('current_level_order', $levelOrders))
                  ->orWhereHas('validationLogs', fn ($sub) => $sub->whereIn('validation_level_id', $levelIds));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->string('date_to'));
        }

        return $query;
    }

    private function exportOrdersExcel($orders, string $prefix): \Symfony\Component\HttpFoundation\Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Validations');

        $headers = ['BC', 'Titre', 'Demandeur', 'Boutique', 'Montant (XOF)', 'Statut', 'Niveau courant', 'Soumise le'];
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $labels = ['draft' => 'Brouillon', 'pending' => 'En attente', 'needs_revision' => 'Révision demandée', 'approved' => 'Approuvée', 'rejected' => 'Rejetée', 'cancelled' => 'Annulée'];
        foreach ($orders as $row => $order) {
            $values = [
                $order->order_number ?? '',
                $order->title,
                $order->user?->name ?? '',
                $order->boutique?->name ?? '',
                (float) $order->amount,
                $labels[$order->status] ?? $order->status,
                $order->current_level_order ? 'Niveau ' . $order->current_level_order : '',
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
        return response()->streamDownload(fn () => $writer->save('php://output'), $prefix . '-' . now()->format('Y-m-d') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizeValidation($purchaseOrder);

        $purchaseOrder->load([
            'user',
            'boutique',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
            'comments.user',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('Validations/Show', [
            'order'  => $purchaseOrder,
            'levels' => $levels,
        ]);
    }

    public function showFromHistory(PurchaseOrder $purchaseOrder): Response
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            $levelOrders = $user->validatableLevelOrders();
            abort_unless(count($levelOrders) > 0, 403, 'Vous n\'avez aucun niveau de validation actif.');

            $levelIds = ValidationLevel::whereIn('order', $levelOrders)->pluck('id');

            // Vérifier que cette commande a bien été traitée par ce validateur
            $hasProcessed = $purchaseOrder->validationLogs()
                ->whereIn('validation_level_id', $levelIds)
                ->exists();

            $isCurrentlyAtLevel = $purchaseOrder->status === 'pending'
                && in_array($purchaseOrder->current_level_order, $levelOrders);

            abort_unless($hasProcessed || $isCurrentlyAtLevel, 403, 'Accès non autorisé à cette commande.');
        }

        $purchaseOrder->load([
            'user',
            'boutique',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
            'comments.user',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('Validations/Show', [
            'order'      => $purchaseOrder,
            'levels'     => $levels,
            'readOnly'   => true,
        ]);
    }

    public function approve(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeValidation($purchaseOrder);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $purchaseOrder->current_level_order)->firstOrFail();

        $notifications = [];

        DB::transaction(function () use ($purchaseOrder, $user, $currentLevel, &$notifications) {
            ValidationLog::create([
                'purchase_order_id'   => $purchaseOrder->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'approved',
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->order),
            ]);

            $nextLevel = ValidationLevel::nextAfter($currentLevel->order);

            if ($nextLevel) {
                $purchaseOrder->update([
                    'current_level_order' => $nextLevel->order,
                ]);

                foreach ($nextLevel->validators as $validator) {
                    $notifications[] = [$validator, new OrderApprovedAtLevelNotification($purchaseOrder, $currentLevel, $nextLevel)];
                }
            } else {
                $purchaseOrder->update([
                    'status'              => 'approved',
                    'current_level_order' => null,
                ]);

                $notifications[] = [$purchaseOrder->user, new OrderFinallyApprovedNotification($purchaseOrder)];

                $caissiers = User::whereHas('role', fn ($q) => $q->where('slug', 'caissier'))
                    ->when($purchaseOrder->boutique_id, fn ($q) => $q->where('boutique_id', $purchaseOrder->boutique_id))
                    ->get();

                foreach ($caissiers as $caissier) {
                    $notifications[] = [$caissier, new DecaissementPretNotification($purchaseOrder)];
                }
            }
        });

        foreach ($notifications as [$notifiable, $notification]) {
            try {
                $notifiable->notify($notification);
            } catch (\Throwable) {
                // WhatsApp failure must not block the approval
            }
        }

        return redirect()->route('validations.index')
            ->with('success', 'Commande approuvée avec succès.');
    }

    public function reject(RejectValidationRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeValidation($purchaseOrder);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $purchaseOrder->current_level_order)->firstOrFail();

        $notifiable    = null;
        $notification  = null;

        DB::transaction(function () use ($request, $purchaseOrder, $user, $currentLevel, &$notifiable, &$notification) {
            ValidationLog::create([
                'purchase_order_id'   => $purchaseOrder->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'rejected',
                'comment'             => $request->comment,
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->order),
            ]);

            $purchaseOrder->update([
                'status'              => 'rejected',
                'current_level_order' => null,
            ]);

            $notifiable   = $purchaseOrder->user;
            $notification = new OrderRejectedNotification($purchaseOrder, $currentLevel, $request->comment);
        });

        try {
            $notifiable?->notify($notification);
        } catch (\Throwable) {
            // WhatsApp failure must not block the rejection
        }

        return redirect()->route('validations.index')
            ->with('success', 'Commande refusée.');
    }

    private function authorizeValidation(PurchaseOrder $purchaseOrder): void
    {
        $user = auth()->user();

        abort_unless($purchaseOrder->isPending(), 403, 'Cette commande n\'est plus en attente de validation.');

        if ($user->isAdmin()) {
            return;
        }

        $validatableOrders = $user->validatableLevelOrders();
        abort_unless(count($validatableOrders) > 0, 403, 'Vous n\'avez aucun niveau de validation actif.');
        abort_unless(
            in_array($purchaseOrder->current_level_order, $validatableOrders),
            403,
            'Cette commande n\'est pas à votre niveau de validation.'
        );
    }
}
