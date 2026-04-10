<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Export Commandes</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .page { padding: 24px 28px; }

        .header { margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-end; }
        .header h1 { font-size: 18px; font-weight: 700; color: #0f172a; }
        .header p  { font-size: 10px; color: #64748b; margin-top: 3px; }
        .header-right { text-align: right; font-size: 10px; color: #94a3b8; }

        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #4f46e5; }
        thead th { padding: 7px 8px; text-align: left; font-size: 9px; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: 0.05em; }
        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 6px 8px; font-size: 10px; color: #334155; vertical-align: middle; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9px; font-weight: 600; }
        .badge-draft    { background: #f1f5f9; color: #475569; }
        .badge-pending  { background: #fef9c3; color: #854d0e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }

        .amount { font-weight: 700; text-align: right; }
        .validators { font-size: 9px; color: #4f46e5; }
        .footer { margin-top: 16px; padding-top: 10px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; }
        .summary { margin-bottom: 16px; display: flex; gap: 16px; }
        .summary-card { padding: 8px 14px; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc; }
        .summary-card .val { font-size: 16px; font-weight: 800; color: #0f172a; }
        .summary-card .lbl { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <h1>Export — Bons de Commande</h1>
            <p>{{ $orders->count() }} commande{{ $orders->count() > 1 ? 's' : '' }} exportee{{ $orders->count() > 1 ? 's' : '' }}</p>
        </div>
        <div class="header-right">
            Généré le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('DD MMMM YYYY [à] HH:mm') }}<br>
            {{ config('app.name') }}
        </div>
    </div>

    @php
        $totalAmount   = $orders->sum('amount');
        $countApproved = $orders->where('status', 'approved')->count();
        $countPending  = $orders->where('status', 'pending')->count();
        $countRejected = $orders->where('status', 'rejected')->count();
        $statusLabels  = ['draft' => 'Brouillon', 'pending' => 'En attente', 'approved' => 'Approuvee', 'rejected' => 'Refusee'];
    @endphp

    <div class="summary">
        <div class="summary-card">
            <div class="val">{{ $orders->count() }}</div>
            <div class="lbl">Total</div>
        </div>
        <div class="summary-card">
            <div class="val" style="color:#166534;">{{ $countApproved }}</div>
            <div class="lbl">Approuvees</div>
        </div>
        <div class="summary-card">
            <div class="val" style="color:#854d0e;">{{ $countPending }}</div>
            <div class="lbl">En attente</div>
        </div>
        <div class="summary-card">
            <div class="val" style="color:#991b1b;">{{ $countRejected }}</div>
            <div class="lbl">Refusees</div>
        </div>
        <div class="summary-card">
            <div class="val">{{ number_format($totalAmount, 0, ',', ' ') }}</div>
            <div class="lbl">Montant total (XOF)</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Titre</th>
                <th>Boutique</th>
                <th>Demandeur</th>
                <th style="text-align:right;">Montant (XOF)</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Validateurs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td style="color:#94a3b8;">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</td>
                <td style="max-width:140px;">{{ $order->title }}</td>
                <td>{{ $order->boutique?->name ?? '—' }}</td>
                <td>{{ $order->user?->name ?? '—' }}</td>
                <td class="amount">{{ number_format($order->amount, 0, ',', ' ') }}</td>
                <td><span class="badge badge-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</td>
                <td class="validators">
                    @foreach($order->validationLogs->where('action', 'approved')->sortBy(fn($l) => $l->validationLevel?->order) as $log)
                        {{ $log->validationLevel?->name }}: {{ $log->user?->name }}<br>
                    @endforeach
                    @if($order->validationLogs->where('action', 'rejected')->count())
                        @php $rej = $order->validationLogs->firstWhere('action', 'rejected'); @endphp
                        <span style="color:#ef4444;">Refuse par {{ $rej?->user?->name }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <span>{{ config('app.name') }} &mdash; Export commandes</span>
        <span>Généré le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('DD/MM/YYYY HH:mm') }}</span>
    </div>
</div>
</body>
</html>
