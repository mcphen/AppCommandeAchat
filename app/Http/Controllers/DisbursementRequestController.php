<?php

namespace App\Http\Controllers;

use App\Models\Boutique;
use App\Models\AppSetting;
use App\Models\Company;
use App\Models\DisbursementRequest;
use App\Models\DisbursementRequestAttachment;
use App\Models\NatureOperation;
use App\Models\User;
use App\Models\ValidationLevel;
use App\Helpers\PdfHelper;
use App\Notifications\DisbursementRequestSubmittedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response;

class DisbursementRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $user  = $request->user();
        $query = $this->buildIndexQuery($request, $user);

        $orders = $query->paginate(15)->withQueryString();

        // Admin et utilisateurs sans boutique voient tous les demandeurs ; boutique = filtre par boutique
        $demandeurs = ($user->isAdmin() || ! $user->boutique_id)
            ? User::whereHas('role', fn ($q) => $q->whereIn('slug', ['demandeur', 'validateur']))->orderBy('name')->get(['id', 'name'])
            : User::where('boutique_id', $user->boutique_id)->orderBy('name')->get(['id', 'name']);

        // Seuls les utilisateurs sans boutique (et l'admin) peuvent filtrer par boutique
        $boutiques = (! $user->boutique_id)
            ? Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('DisbursementRequests/Index', [
            'orders'          => $orders,
            'natureOperations' => NatureOperation::active()->orderBy('name')->get(['id', 'name']),
            'boutiques'       => $boutiques,
            'demandeurs'      => $demandeurs,
            'levelsCount'     => ValidationLevel::count(),
            'filters'         => $this->getFilters($request),
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $orders = $this->buildIndexQuery($request, $request->user())
            ->with(['boutique', 'user', 'natureOperation'])
            ->get();

        return $this->exportExcel($orders);
    }

    private function buildIndexQuery(Request $request, User $user)
    {
        $query = DisbursementRequest::with(['boutique', 'user', 'natureOperation', 'validationLogs.validationLevel'])->latest();

        if (! $user->isAdmin()) {
            if ($user->boutique_id) {
                // Utilisateur rattaché à une boutique : restreint aux demandes de sa boutique
                $boutiqueUserIds = User::where('boutique_id', $user->boutique_id)->pluck('id');
                $query->whereIn('user_id', $boutiqueUserIds);
            }
            // Utilisateur sans boutique (non-admin) : voit toutes les demandes
        }

        if ($request->filled('boutique_id')) {
            $query->where('boutique_id', $request->integer('boutique_id'));
        }

        if ($request->filled('nature_operation_id')) {
            $query->where('nature_operation_id', $request->integer('nature_operation_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
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
            'boutique_id'         => $request->string('boutique_id')->toString(),
            'nature_operation_id' => $request->string('nature_operation_id')->toString(),
            'status'              => $request->string('status')->toString(),
            'user_id'             => $request->string('user_id')->toString(),
            'date_from'           => $request->string('date_from')->toString(),
            'date_to'             => $request->string('date_to')->toString(),
            'search'              => $request->string('search')->toString(),
        ];
    }

    private function exportExcel($orders): \Symfony\Component\HttpFoundation\Response
    {
        $statusLabels = [
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'needs_revision' => 'Révision demandée',
            'approved' => 'Approuvée',
            'rejected' => 'Refusée',
            'cancelled' => 'Annulée',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Decaissements');

        $headers = ['Référence', 'Titre', 'Boutique', 'Demandeur', 'Nature', 'Montant (XOF)', 'Statut', 'Créée le', 'Soumise le'];

        foreach ($headers as $col => $header) {
            $cell = Coordinate::stringFromColumnIndex($col + 1) . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('4F46E5');
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }

        foreach ($orders as $row => $order) {
            $rowData = [
                $order->reference,
                $order->title,
                $order->boutique?->name ?? '',
                $order->user?->name ?? '',
                $order->natureOperation?->name ?? '',
                (float) $order->amount,
                $statusLabels[$order->status] ?? $order->status,
                $order->created_at?->format('d/m/Y'),
                $order->submitted_at?->format('d/m/Y') ?? '',
            ];

            foreach ($rowData as $col => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . ($row + 2), $value);
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'demandes-decaissement-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();

        return Inertia::render('DisbursementRequests/Create', [
            'boutique'         => $user->boutique,
            'boutiques'        => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'companies'        => Company::active()->orderBy('name')->get(['id', 'name', 'logo']),
            'natureOperations' => NatureOperation::active()->orderBy('name')->get(['id', 'name', 'description']),
        ]);
    }

    public function storeNatureOperation(Request $request): JsonResponse
    {
        $slug = NatureOperation::makeSlug($request->string('name')->toString());

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('nature_operations', 'slug')],
            'description' => 'nullable|string|max:500',
        ], [
            'name.unique' => "Cette nature d'opération existe déjà.",
        ]);

        $natureOperation = NatureOperation::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'natureOperation' => $natureOperation->only(['id', 'name', 'slug', 'description', 'is_active']),
        ], 201);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nature_operation_id' => 'required|exists:nature_operations,id',
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:5000',
            'amount'              => 'required|numeric|min:1',
            'boutique_id'         => 'nullable|exists:boutiques,id',
            'company_id'          => 'nullable|exists:companies,id',
            'attachments.*'       => 'nullable|file|max:10240',
        ]);

        $boutique = $request->user()->boutique
            ?? ($request->boutique_id ? Boutique::find($request->boutique_id) : null);

        $dr = DB::transaction(function () use ($validated, $boutique, $request) {
            $dr = DisbursementRequest::create([
                'user_id'             => auth()->id(),
                'boutique_id'         => $boutique?->id,
                'company_id'          => $validated['company_id'] ?? null,
                'nature_operation_id' => $validated['nature_operation_id'],
                'title'               => $validated['title'],
                'description'         => $validated['description'] ?? null,
                'amount'              => $validated['amount'],
                'status'              => 'draft',
            ]);

            $this->storeAttachments($dr, $request);

            return $dr;
        });

        if ($request->boolean('and_submit')) {
            return $this->doSubmit($dr);
        }

        return redirect()->route('disbursement-requests.show', $dr)
            ->with('success', 'Demande enregistrée en brouillon.');
    }

    public function show(DisbursementRequest $disbursementRequest): Response
    {
        $this->authorizeView($disbursementRequest);

        $disbursementRequest->load([
            'user',
            'boutique',
            'company',
            'natureOperation',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
            'validationLogs.delegatedBy',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('DisbursementRequests/Show', [
            'order'     => $disbursementRequest,
            'levels'    => $levels,
            'companies' => Company::active()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function assignCompany(Request $request, DisbursementRequest $disbursementRequest): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $disbursementRequest->update(['company_id' => $validated['company_id']]);

        return back()->with('success', 'Entreprise mise à jour.');
    }

    public function edit(DisbursementRequest $disbursementRequest): Response
    {
        $user = auth()->user();
        abort_unless(
            ($disbursementRequest->user_id === $user->id || $user->isAdmin())
            && ($disbursementRequest->isDraft() || $disbursementRequest->isNeedsRevision()),
            403
        );

        $disbursementRequest->load(['attachments', 'boutique', 'natureOperation', 'company']);

        return Inertia::render('DisbursementRequests/Edit', [
            'order'            => $disbursementRequest,
            'boutiques'        => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'companies'        => Company::active()->orderBy('name')->get(['id', 'name', 'logo']),
            'natureOperations' => NatureOperation::active()->orderBy('name')->get(['id', 'name', 'description']),
        ]);
    }

    public function update(Request $request, DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            ($disbursementRequest->user_id === $user->id || $user->isAdmin())
            && ($disbursementRequest->isDraft() || $disbursementRequest->isNeedsRevision()),
            403
        );

        $validated = $request->validate([
            'nature_operation_id'    => 'required|exists:nature_operations,id',
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string|max:5000',
            'amount'                 => 'required|numeric|min:1',
            'company_id'             => 'nullable|exists:companies,id',
            'attachments.*'          => 'nullable|file|max:10240',
            'deleted_attachment_ids' => 'nullable|array',
            'deleted_attachment_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($validated, $disbursementRequest, $request) {
            $disbursementRequest->update([
                'nature_operation_id' => $validated['nature_operation_id'],
                'title'               => $validated['title'],
                'description'         => $validated['description'] ?? null,
                'amount'              => $validated['amount'],
                'company_id'          => $validated['company_id'] ?? null,
            ]);

            if ($request->deleted_attachment_ids) {
                $toDelete = $disbursementRequest->attachments()
                    ->whereIn('id', $request->deleted_attachment_ids)
                    ->get();
                foreach ($toDelete as $att) {
                    Storage::disk('private')->delete($att->file_path);
                    $att->delete();
                }
            }

            $this->storeAttachments($disbursementRequest, $request);
        });

        return redirect()->route('disbursement-requests.show', $disbursementRequest)
            ->with('success', 'Demande mise à jour.');
    }

    public function destroy(DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            ($disbursementRequest->user_id === $user->id || $user->isAdmin())
            && ($disbursementRequest->isDraft() || $disbursementRequest->isRejected()),
            403
        );

        foreach ($disbursementRequest->attachments as $att) {
            Storage::disk('private')->delete($att->file_path);
        }

        $disbursementRequest->delete();

        return redirect()->route('disbursement-requests.index')
            ->with('success', 'Demande supprimée.');
    }

    public function submit(DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            ($disbursementRequest->user_id === $user->id || $user->isAdmin())
            && ($disbursementRequest->isDraft() || $disbursementRequest->isNeedsRevision()),
            403
        );

        return $this->doSubmit($disbursementRequest);
    }

    public function cancel(DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            ($disbursementRequest->user_id === $user->id || $user->isAdmin())
            && ($disbursementRequest->isDraft() || $disbursementRequest->isPending()),
            403
        );

        $disbursementRequest->update(['status' => 'cancelled', 'current_level_order' => null]);

        return back()->with('success', 'Demande annulée.');
    }

    private function doSubmit(DisbursementRequest $disbursementRequest): RedirectResponse
    {
        $firstLevel = ValidationLevel::first_level();

        abort_unless($firstLevel, 422, 'Aucun niveau de validation configuré.');

        $disbursementRequest->update([
            'status'              => 'pending',
            'current_level_order' => $firstLevel->order,
            'submitted_at'        => now(),
        ]);

        foreach ($firstLevel->validators as $validator) {
            $validator->notify(new DisbursementRequestSubmittedNotification($disbursementRequest, $firstLevel));
        }

        return redirect()->route('disbursement-requests.show', $disbursementRequest)
            ->with('success', 'Demande soumise à validation.');
    }

    public function downloadAttachment(DisbursementRequest $disbursementRequest, DisbursementRequestAttachment $attachment): StreamedResponse
    {
        $this->authorizeView($disbursementRequest);

        abort_unless($attachment->disbursement_request_id === $disbursementRequest->id, 404);
        abort_unless(Storage::disk('private')->exists($attachment->file_path), 404);

        return Storage::disk('private')->download($attachment->file_path, $attachment->file_name);
    }

    public function downloadPdf(DisbursementRequest $disbursementRequest): HttpResponse
    {
        $this->authorizeView($disbursementRequest);

        $disbursementRequest->load([
            'user',
            'boutique',
            'company',
            'natureOperation',
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
        ]);

        $levels = \App\Models\ValidationLevel::orderBy('order')->get();

        // Priorité : entreprise liée à la demande → sinon paramètres globaux
        $requestCompany = $disbursementRequest->company;
        if ($requestCompany) {
            $companyData = [
                'company_name'    => $requestCompany->name,
                'company_address' => $requestCompany->address,
                'company_phone'   => $requestCompany->phone,
                'company_email'   => $requestCompany->email,
                'company_nif'     => $requestCompany->nif,
                'company_rccm'    => $requestCompany->rccm,
                'company_logo'    => $requestCompany->logo,
            ];
            $logoB64 = PdfHelper::logoBase64($requestCompany->logo);
        } else {
            $settings    = AppSetting::allAsArray();
            $companyData = $settings;
            $logoB64     = PdfHelper::logoBase64($settings['company_logo'] ?? null);
        }

        $pdf = Pdf::loadView('pdf.disbursement_request', [
            'order'   => $disbursementRequest,
            'levels'  => $levels,
            'company' => $companyData,
            'logoB64' => $logoB64,
        ])->setPaper('a4', 'portrait');

        $filename = 'decaissement-' . $disbursementRequest->reference . '.pdf';

        return $pdf->download($filename);
    }

    private function authorizeView(DisbursementRequest $dr): void
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isValidateur() || $user->isCaissier()) {
            return;
        }

        if ($user->isDemandeur() && $user->boutique_id) {
            $boutiqueUserIds = User::where('boutique_id', $user->boutique_id)->pluck('id');
            abort_unless($boutiqueUserIds->contains($dr->user_id), 403);
            return;
        }

        abort_unless($dr->user_id === $user->id, 403);
    }

    private function storeAttachments(DisbursementRequest $dr, Request $request): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('disbursement-requests/' . $dr->id, 'private');
            DisbursementRequestAttachment::create([
                'disbursement_request_id' => $dr->id,
                'file_path'               => $path,
                'file_name'               => $file->getClientOriginalName(),
                'file_size'               => $file->getSize(),
            ]);
        }
    }
}
