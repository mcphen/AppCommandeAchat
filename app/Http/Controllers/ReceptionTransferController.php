<?php
namespace App\Http\Controllers;
use App\Models\AppSetting;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReception;
use App\Models\PurchaseOrderReceptionLine;
use App\Models\ReceptionTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
class ReceptionTransferController extends Controller {

    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $visibleOrders = fn ($query) => $query
            ->where('status', 'approved')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id));

        $query = ReceptionTransfer::with([
            'project',
            'projectResponsible',
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
                $q->where('transfer_number', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('project', fn ($project) => $project->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('reception.purchaseOrder', fn ($order) => $order->where('title', 'like', "%{$search}%")->orWhere('order_number', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('user_id')) $query->where('transferred_by', $request->integer('user_id'));
        if ($request->filled('reception_id')) $query->where('reception_id', $request->integer('reception_id'));

        $statsQuery = ReceptionTransfer::where('status', 'confirmed')->whereHas('reception.purchaseOrder', $visibleOrders);
        $lineStatsQuery = \App\Models\ReceptionTransferLine::whereHas('transfer', fn ($transfer) => $transfer->where('status', 'confirmed'))->whereHas('transfer.reception.purchaseOrder', $visibleOrders);
        $availableLines = PurchaseOrderReceptionLine::withSum('confirmedTransferLines as transferred_total', 'quantity_transferred')
            ->whereHas('reception.purchaseOrder', $visibleOrders)
            ->get();


        $availableReceptions = PurchaseOrderReception::with([
            'purchaseOrder:id,title,order_number,user_id,status',
            'lines.orderLine.article',
            'lines.confirmedTransferLines',
        ])
            ->whereHas('purchaseOrder', $visibleOrders)
            ->latest('received_at')
            ->get()
            ->map(function ($reception) {
                $lines = $reception->lines->map(function ($line) {
                    $transferred = (float) $line->confirmedTransferLines->sum('quantity_transferred');
                    $available = max(0, (float) $line->quantity_received - $transferred);
                    return [
                        'id' => $line->id,
                        'label' => $line->orderLine?->article?->name ?? "Ligne #{$line->purchase_order_line_id}",
                        'article_reference' => $line->orderLine?->article?->reference,
                        'line_note' => $line->orderLine?->note,
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
            'projects' => Project::with('currentResponsibleAssignment.user')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'availableReceptions' => $availableReceptions,
            'filters' => [
                'project_id' => $request->string('project_id')->toString(),
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
                'user_id' => $request->string('user_id')->toString(),
                'reception_id' => $request->string('reception_id')->toString(),
            ],
            'users' => \App\Models\User::orderBy('name')->get(['id','name']),
            'receptionOptions' => PurchaseOrderReception::with('purchaseOrder:id,order_number,title')->latest('received_at')->get(['id','purchase_order_id','received_at']),
            'stats' => [
                'transfers' => (clone $statsQuery)->count(),
                'projects' => (clone $statsQuery)->distinct()->count('project_id'),
                'transferred_quantity' => (float) $lineStatsQuery->sum('quantity_transferred'),
                'pending_quantity' => $availableLines->sum(fn ($line) => max(0, (float) $line->quantity_received - (float) ($line->transferred_total ?? 0))),
            ],
        ]);
    }

    public function create(Request $request): \Inertia\Response
    {
        return \Inertia\Inertia::render('Transfers/Create', [
            'projects' => Project::with('currentResponsibleAssignment.user')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'availableReceptions' => $this->availableReceptions($request),
        ]);
    }

    public function show(Request $request, ReceptionTransfer $transfer): \Inertia\Response
    {
        $this->authorizeTransfer($request, $transfer);
        $transfer->load(['project', 'projectResponsible', 'actor', 'reception.purchaseOrder.fournisseur', 'reception.purchaseOrder.boutique', 'lines.receptionLine.orderLine.article']);

        return \Inertia\Inertia::render('Transfers/Show', ['transfer' => $transfer]);
    }

    public function pdf(Request $request, ReceptionTransfer $transfer): HttpResponse
    {
        $this->authorizeTransfer($request, $transfer);
        $transfer->load(['project', 'projectResponsible', 'actor', 'reception.purchaseOrder.fournisseur', 'reception.purchaseOrder.boutique', 'lines.receptionLine.orderLine.article']);
        $settings = AppSetting::allAsArray();
        $logoB64 = null;
        if (! empty($settings['company_logo'])) {
            $path = storage_path('app/public/'.$settings['company_logo']);
            if (file_exists($path)) $logoB64 = 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
        }

        $signatureB64 = function (?string $relativePath): ?string {
            if (! $relativePath) return null;
            $path = storage_path('app/public/'.$relativePath);
            return file_exists($path) ? 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path)) : null;
        };
        $dispatchSignature = $transfer->dispatch_signed_at ? $signatureB64($transfer->actor?->signature_path) : null;
        $siteSignature = $transfer->site_signed_at ? $signatureB64($transfer->projectResponsible?->signature_path) : null;

        $pdf = Pdf::loadView('pdf.reception_transfer', compact('transfer', 'settings', 'logoB64', 'dispatchSignature', 'siteSignature'))->setPaper('a4');
        return $request->boolean('print') ? $pdf->stream('bon-transfert-'.$transfer->id.'.pdf') : $pdf->download('bon-transfert-'.$transfer->id.'.pdf');
    }

    private function authorizeTransfer(Request $request, ReceptionTransfer $transfer): void
    {
        $transfer->loadMissing('reception.purchaseOrder');
        abort_unless($request->user()->isAdmin() || $transfer->reception->purchaseOrder->user_id === $request->user()->id, 403);
    }

    private function availableReceptions(Request $request): array
    {
        $user = $request->user();
        $visibleOrders = fn ($query) => $query->where('status', 'approved')->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id));
        return PurchaseOrderReception::with(['purchaseOrder:id,title,order_number,user_id,status', 'lines.orderLine.article', 'lines.confirmedTransferLines'])
            ->whereHas('purchaseOrder', $visibleOrders)->latest('received_at')->get()->map(function ($reception) {
                $lines = $reception->lines->map(function ($line) {
                    $transferred = (float) $line->confirmedTransferLines->sum('quantity_transferred');
                    return [
                        'id' => $line->id,
                        'label' => $line->orderLine?->article?->name ?? "Ligne #{$line->purchase_order_line_id}",
                        'article_reference' => $line->orderLine?->article?->reference,
                        'line_note' => $line->orderLine?->note,
                        'unit' => $line->orderLine?->article?->unit ?? '',
                        'quantity_received' => (float) $line->quantity_received,
                        'quantity_transferred' => $transferred,
                        'quantity_available' => max(0, (float) $line->quantity_received - $transferred),
                    ];
                })->filter(fn ($line) => $line['quantity_available'] > 0)->values();
                return ['id' => $reception->id, 'received_at' => $reception->received_at, 'purchase_order' => $reception->purchaseOrder, 'lines' => $lines];
            })->filter(fn ($reception) => $reception['lines']->isNotEmpty())->values()->all();
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
                $available = (float) $line->quantity_received - (float) $line->confirmedTransferLines()->sum('quantity_transferred');
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

    public function storeProject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:projects,code'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Project::create([...$data, 'is_active' => true]);

        return back()->with('success', 'Chantier ajouté et disponible pour le transfert.');
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reception_id' => ['required', 'integer', 'exists:purchase_order_receptions,id'],
            'transfers' => ['required', 'array', 'min:1'],
            'transfers.*.project_id' => ['required', 'integer', 'exists:projects,id'],
            'transfers.*.project_responsible_id' => ['required', 'integer', 'exists:users,id'],
            'mode' => ['nullable', 'in:draft,confirmed'],
            'transfers.*.transferred_at' => ['required', 'date'],
            'transfers.*.reference' => ['nullable', 'string', 'max:100'],
            'transfers.*.notes' => ['nullable', 'string', 'max:1000'],
            'transfers.*.lines' => ['required', 'array', 'min:1'],
            'transfers.*.lines.*.reception_line_id' => ['required', 'integer'],
            'transfers.*.lines.*.quantity_transferred' => ['required', 'numeric', 'gt:0'],
        ]);

        $reception = PurchaseOrderReception::with('purchaseOrder')->findOrFail($data['reception_id']);
        $order = $reception->purchaseOrder;
        $user = $request->user();
        abort_unless($user->isAdmin() || $order->user_id === $user->id, 403);
        abort_unless($order->status === 'approved', 422, 'Le bon de commande doit être entièrement approuvé.');

        $projectIds = collect($data['transfers'])->pluck('project_id')->unique();
        abort_unless(Project::whereIn('id', $projectIds)->where('is_active', true)->count() === $projectIds->count(), 422, 'Un chantier sélectionné est inactif.');

        DB::transaction(function () use ($data, $reception, $user) {
            $inputs = collect($data['transfers'])->flatMap(fn ($transfer) => $transfer['lines']);
            $ids = $inputs->pluck('reception_line_id')->unique();
            $lines = PurchaseOrderReceptionLine::where('reception_id', $reception->id)
                ->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            if ($lines->count() !== $ids->count()) {
                throw ValidationException::withMessages(['transfers' => 'Une ligne ne fait pas partie de cette réception.']);
            }

            foreach ($inputs->groupBy('reception_line_id') as $lineId => $allocations) {
                $line = $lines[$lineId];
                $available = (float) $line->quantity_received - (float) $line->confirmedTransferLines()->sum('quantity_transferred');
                $requested = (float) $allocations->sum('quantity_transferred');
                if ($requested > $available) {
                    throw ValidationException::withMessages(['transfers' => "Quantité totale demandée {$requested}, disponible {$available} pour {$lineId}."]);
                }
            }

            foreach ($data['transfers'] as $input) {
                $transfer = ReceptionTransfer::create([
                    'reception_id' => $reception->id,
                    'project_id' => $input['project_id'],
                    'project_responsible_id' => $input['project_responsible_id'],
                    'status' => ($data['mode'] ?? 'confirmed'),
                    'confirmed_at' => ($data['mode'] ?? 'confirmed') === 'confirmed' ? now() : null,
                    'transferred_by' => $user->id,
                    'transferred_at' => $input['transferred_at'],
                    'reference' => $input['reference'] ?? null,
                    'notes' => $input['notes'] ?? null,
                ]);
                $transfer->lines()->createMany($input['lines']);
            }
        });

        return back()->with('success', count($data['transfers']).' transfert(s) enregistré(s).');
    }
    public function cancel(Request $request, ReceptionTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($request, $transfer);
        abort_unless($transfer->status !== 'cancelled', 422, 'Ce bon est déjà annulé.');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $transfer->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $request->user()->id, 'cancellation_reason' => $data['reason']]);
        return back()->with('success', 'Bon de transfert annulé sans suppression de l’historique.');
    }

    public function signDispatch(Request $request, ReceptionTransfer $transfer): RedirectResponse
    {
        abort_unless($transfer->transferred_by === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_unless($request->user()->signature_path, 422, 'Ajoutez votre signature dans votre profil.');
        $transfer->update(['dispatch_signed_at' => now()]);
        return back()->with('success', 'Sortie signée.');
    }

    public function signSite(Request $request, ReceptionTransfer $transfer): RedirectResponse
    {
        abort_unless($transfer->project_responsible_id === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_unless($request->user()->signature_path, 422, 'Ajoutez votre signature dans votre profil.');
        $transfer->update(['site_signed_at' => now()]);
        return back()->with('success', 'Réception chantier signée.');
    }
    public function edit(Request $request, ReceptionTransfer $transfer): \Inertia\Response
    {
        $this->authorizeTransfer($request, $transfer); abort_unless($transfer->status === 'draft', 422, 'Seul un brouillon est modifiable.');
        $transfer->load('lines');
        return \Inertia\Inertia::render('Transfers/Create', ['projects'=>Project::with('currentResponsibleAssignment.user')->where('is_active',true)->orderBy('name')->get(['id','code','name']),'availableReceptions'=>$this->availableReceptions($request),'draft'=>$transfer]);
    }

    public function updateDraft(Request $request, ReceptionTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($request,$transfer); abort_unless($transfer->status==='draft',422,'Seul un brouillon est modifiable.');
        $data=$request->validate(['project_id'=>'required|exists:projects,id','project_responsible_id'=>'required|exists:users,id','transferred_at'=>'required|date','reference'=>'nullable|string|max:100','notes'=>'nullable|string|max:1000','lines'=>'required|array|min:1','lines.*.reception_line_id'=>'required|integer','lines.*.quantity_transferred'=>'required|numeric|gt:0']);
        DB::transaction(function()use($transfer,$data){$transfer->update(collect($data)->except('lines')->all());$transfer->lines()->delete();$transfer->lines()->createMany($data['lines']);}); return redirect()->route('transfers.show',$transfer)->with('success','Brouillon mis à jour.');
    }

    public function confirm(Request $request, ReceptionTransfer $transfer): RedirectResponse
    {
        $this->authorizeTransfer($request,$transfer); abort_unless($transfer->status==='draft',422,'Ce bon ne peut pas être confirmé.');
        DB::transaction(function()use($transfer){$transfer->load('lines.receptionLine');foreach($transfer->lines as $item){$available=(float)$item->receptionLine->quantity_received-(float)$item->receptionLine->confirmedTransferLines()->sum('quantity_transferred');if((float)$item->quantity_transferred>$available)throw ValidationException::withMessages(['transfer'=>'Quantité devenue indisponible pour une ligne.']);}$transfer->update(['status'=>'confirmed','confirmed_at'=>now()]);});return back()->with('success','Bon confirmé.');
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $query=ReceptionTransfer::with(['project','projectResponsible','actor','reception.purchaseOrder','lines'])
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('reception.purchaseOrder', fn ($order) => $order->where('user_id', $user->id)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('transferred_by', $request->integer('user_id')))
            ->when($request->filled('reception_id'), fn ($q) => $q->where('reception_id', $request->integer('reception_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('transferred_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('transferred_at', '<=', $request->date('date_to')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($sub) => $sub->where('transfer_number', 'like', '%'.$request->string('search').'%')->orWhere('reference', 'like', '%'.$request->string('search').'%')))
            ->latest('transferred_at');
        return response()->streamDownload(function()use($query){$out=fopen('php://output','w');fputcsv($out,['Numéro','Statut','Date','BC source','Chantier','Responsable chantier','Effectué par','Quantité']);$query->chunk(200,function($items)use($out){foreach($items as $t)fputcsv($out,[$t->transfer_number,$t->status,$t->transferred_at->format('d/m/Y'),$t->reception?->purchaseOrder?->order_number,$t->project?->name,$t->projectResponsible?->name,$t->actor?->name,$t->lines->sum('quantity_transferred')]);});fclose($out);},'transferts-'.now()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }}