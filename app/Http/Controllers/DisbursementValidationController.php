<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\DisbursementRequest;
use App\Models\Decaissement;
use App\Models\ModeReglement;
use App\Models\ValidationLevel;
use App\Models\ValidationLog;
use App\Notifications\DisbursementRequestApprovedAtLevelNotification;
use App\Notifications\DisbursementRequestFinallyApprovedNotification;
use App\Notifications\DisbursementRequestRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DisbursementValidationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $this->buildIndexQuery($request, $user);

        $orders = $query->paginate(10)->withQueryString();

        return Inertia::render('DisbursementValidations/Index', [
            'orders'      => $orders,
            'boutiques'   => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'levelsCount' => ValidationLevel::count(),
            'filters'     => [
                'boutique_id' => $request->string('boutique_id')->toString(),
            ],
        ]);
    }

    public function exportIndex(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $orders = $this->buildIndexQuery($request, $request->user())->get();
        return $this->exportOrdersExcel($orders, 'validations-dd-en-attente');
    }

    public function show(DisbursementRequest $disbursementRequest): Response
    {
        $this->authorizeValidation($disbursementRequest);

        $disbursementRequest->load([
            'user',
            'boutique',
            'natureOperation',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('DisbursementValidations/Show', [
            'order'  => $disbursementRequest,
            'levels' => $levels,
        ]);
    }

    public function showFromHistory(DisbursementRequest $disbursementRequest): Response
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            $levelOrders = $user->validatableLevelOrders();
            abort_unless(count($levelOrders) > 0, 403, "Vous n'avez aucun niveau de validation actif.");

            $levelIds = ValidationLevel::whereIn('order', $levelOrders)->pluck('id');

            $hasProcessed = $disbursementRequest->validationLogs()
                ->whereIn('validation_level_id', $levelIds)
                ->exists();

            $isCurrentlyAtLevel = $disbursementRequest->status === 'pending'
                && in_array($disbursementRequest->current_level_order, $levelOrders);

            abort_unless($hasProcessed || $isCurrentlyAtLevel, 403, 'Accès non autorisé à cette demande.');
        }

        $disbursementRequest->load([
            'user',
            'boutique',
            'natureOperation',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('DisbursementValidations/Show', [
            'order'    => $disbursementRequest,
            'levels'   => $levels,
            'readOnly' => true,
        ]);
    }

    public function history(Request $request): Response
    {
        $user = $request->user();
        $query = $this->buildHistoryQuery($request, $user);

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('DisbursementValidations/History', [
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

    public function exportHistory(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $orders = $this->buildHistoryQuery($request, $request->user())->get();
        return $this->exportOrdersExcel($orders, 'historique-validations-dd');
    }

    private function buildIndexQuery(Request $request, $user)
    {
        $query = DisbursementRequest::where('status', 'pending')
            ->with(['user', 'boutique', 'natureOperation', 'attachments'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            $validatableOrders = $user->validatableLevelOrders();
            abort_unless(count($validatableOrders) > 0, 403, "Vous n'avez aucun niveau de validation actif.");
            $query->whereIn('current_level_order', $validatableOrders);
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        return $query;
    }

    private function buildHistoryQuery(Request $request, $user)
    {
        $query = DisbursementRequest::with(['user', 'boutique', 'natureOperation', 'validationLogs.validationLevel'])
            ->latest('submitted_at');

        if (! $user->isAdmin()) {
            $levelOrders = $user->validatableLevelOrders();
            abort_unless(count($levelOrders) > 0, 403, "Vous n'avez aucun niveau de validation actif.");

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
        $sheet->setTitle('Validations DD');

        $headers = ['Référence', 'Titre', 'Demandeur', 'Boutique', 'Nature', 'Montant (XOF)', 'Statut', 'Niveau courant', 'Soumise le'];
        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        $labels = ['draft' => 'Brouillon', 'pending' => 'En attente', 'needs_revision' => 'Révision demandée', 'approved' => 'Approuvée', 'rejected' => 'Refusée', 'cancelled' => 'Annulée'];
        foreach ($orders as $row => $order) {
            $values = [
                $order->reference,
                $order->title,
                $order->user?->name ?? '',
                $order->boutique?->name ?? '',
                $order->natureOperation?->name ?? '',
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

    public function approve(DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $this->authorizeValidation($disbursementRequest);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $disbursementRequest->current_level_order)->firstOrFail();

        $notifications = [];

        DB::transaction(function () use ($disbursementRequest, $user, $currentLevel, &$notifications) {
            ValidationLog::create([
                'validationable_type'  => DisbursementRequest::class,
                'validationable_id'    => $disbursementRequest->id,
                'validation_level_id'  => $currentLevel->id,
                'user_id'              => $user->id,
                'action'               => 'approved',
                'delegated_by_id'      => $user->getDelegatorIdForLevel($currentLevel->order),
            ]);

            $nextLevel = ValidationLevel::nextAfter($currentLevel->order);

            if ($nextLevel) {
                $disbursementRequest->update(['current_level_order' => $nextLevel->order]);

                foreach ($nextLevel->validators as $validator) {
                    $notifications[] = [$validator, new DisbursementRequestApprovedAtLevelNotification(
                        $disbursementRequest, $currentLevel, $nextLevel
                    )];
                }
            } else {
                $disbursementRequest->update([
                    'status'              => 'approved',
                    'current_level_order' => null,
                ]);

                $defaultMode = ModeReglement::first();
                Decaissement::create([
                    'purchase_order_id'  => null,
                    'disbursement_request_id' => $disbursementRequest->id,
                    'montant'            => $disbursementRequest->amount,
                    'mode_reglement_id'  => $defaultMode?->id,
                    'reference'          => null,
                    'notes'              => 'Décaissement automatique suite à approbation finale',
                    'decaissement_date'  => now()->toDateString(),
                    'recorded_by'        => $user->id,
                ]);

                $notifications[] = [$disbursementRequest->user, new DisbursementRequestFinallyApprovedNotification($disbursementRequest)];
            }
        });

        foreach ($notifications as [$notifiable, $notification]) {
            try {
                $notifiable->notify($notification);
            } catch (\Throwable) {
                // WhatsApp failure must not block the approval
            }
        }

        return redirect()->route('disbursement-validations.index')
            ->with('success', 'Demande de décaissement approuvée.');
    }

    public function reject(Request $request, DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $this->authorizeValidation($disbursementRequest);

        $request->validate(['comment' => 'required|string|max:1000']);

        $user         = auth()->user();
        $currentLevel = ValidationLevel::where('order', $disbursementRequest->current_level_order)->firstOrFail();

        $notifiable   = null;
        $notification = null;

        DB::transaction(function () use ($request, $disbursementRequest, $user, $currentLevel, &$notifiable, &$notification) {
            ValidationLog::create([
                'validationable_type' => DisbursementRequest::class,
                'validationable_id'   => $disbursementRequest->id,
                'validation_level_id' => $currentLevel->id,
                'user_id'             => $user->id,
                'action'              => 'rejected',
                'comment'             => $request->comment,
                'delegated_by_id'     => $user->getDelegatorIdForLevel($currentLevel->order),
            ]);

            $disbursementRequest->update([
                'status'              => 'rejected',
                'current_level_order' => null,
            ]);

            $notifiable   = $disbursementRequest->user;
            $notification = new DisbursementRequestRejectedNotification($disbursementRequest, $currentLevel, $request->comment);
        });

        try {
            $notifiable?->notify($notification);
        } catch (\Throwable) {
            // WhatsApp failure must not block the rejection
        }

        return redirect()->route('disbursement-validations.index')
            ->with('success', 'Demande de décaissement refusée.');
    }

    private function authorizeValidation(DisbursementRequest $disbursementRequest): void
    {
        $user = auth()->user();

        abort_unless($disbursementRequest->isPending(), 403, "Cette demande n'est plus en attente de validation.");

        if ($user->isAdmin()) {
            return;
        }

        $validatableOrders = $user->validatableLevelOrders();
        abort_unless(count($validatableOrders) > 0, 403, "Vous n'avez aucun niveau de validation actif.");
        abort_unless(
            in_array($disbursementRequest->current_level_order, $validatableOrders),
            403,
            "Cette demande n'est pas à votre niveau de validation."
        );
    }
}
