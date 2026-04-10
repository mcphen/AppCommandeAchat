<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use App\Models\Boutique;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountingController extends Controller
{
    public function __construct(private AccountingService $accounting) {}

    public function index(Request $request): Response
    {
        $query = AccountingEntry::with(['purchaseOrder.boutique', 'purchaseOrder.receptions'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('piece_ref');

        if ($request->filled('from')) {
            $query->whereDate('entry_date', '>=', $request->string('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('entry_date', '<=', $request->string('to'));
        }

        if ($request->filled('journal_code')) {
            $query->where('journal_code', $request->string('journal_code'));
        }

        if ($request->filled('account_code')) {
            $query->where('account_code', 'like', $request->string('account_code') . '%');
        }

        if ($request->filled('piece_ref')) {
            $query->where('piece_ref', 'like', '%' . $request->string('piece_ref') . '%');
        }

        if ($request->filled('boutique_id')) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('boutique_id', $request->integer('boutique_id')));
        }

        $entries = $query->paginate(50)->withQueryString();

        // Totaux de la page filtrée (non paginée)
        $totals = AccountingEntry::when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->string('to')))
            ->when($request->filled('journal_code'), fn ($q) => $q->where('journal_code', $request->string('journal_code')))
            ->when($request->filled('account_code'), fn ($q) => $q->where('account_code', 'like', $request->string('account_code') . '%'))
            ->when($request->filled('piece_ref'), fn ($q) => $q->where('piece_ref', 'like', '%' . $request->string('piece_ref') . '%'))
            ->when($request->filled('boutique_id'), fn ($q) => $q->whereHas('purchaseOrder', fn ($sq) => $sq->where('boutique_id', $request->integer('boutique_id'))))
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return Inertia::render('Admin/Accounting/Index', [
            'entries'  => $entries,
            'totals'   => [
                'debit'  => (float) ($totals->total_debit ?? 0),
                'credit' => (float) ($totals->total_credit ?? 0),
            ],
            'boutiques' => Boutique::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters'  => $request->only(['from', 'to', 'journal_code', 'account_code', 'piece_ref', 'boutique_id']),
        ]);
    }

    public function export(Request $request, string $format): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(in_array($format, ['fec', 'csv']), 404);

        $query = AccountingEntry::with('purchaseOrder')
            ->orderBy('entry_date')
            ->orderBy('piece_ref');

        if ($request->filled('from')) {
            $query->whereDate('entry_date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('entry_date', '<=', $request->string('to'));
        }
        if ($request->filled('journal_code')) {
            $query->where('journal_code', $request->string('journal_code'));
        }
        if ($request->filled('account_code')) {
            $query->where('account_code', 'like', $request->string('account_code') . '%');
        }
        if ($request->filled('boutique_id')) {
            $query->whereHas('purchaseOrder', fn ($q) => $q->where('boutique_id', $request->integer('boutique_id')));
        }

        $entries  = $query->get();
        $period   = ($request->filled('from') ? $request->string('from') : 'debut') . '_' . ($request->filled('to') ? $request->string('to') : now()->format('Y-m-d'));

        if ($format === 'fec') {
            $content  = $this->accounting->exportFec($entries);
            $filename = "ecritures-fec-{$period}.txt";
            return response($content, 200, [
                'Content-Type'        => 'text/plain; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        // CSV
        $content  = $this->accounting->exportCsv($entries);
        $filename = "ecritures-{$period}.csv";
        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
