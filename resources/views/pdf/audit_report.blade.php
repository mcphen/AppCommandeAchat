<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"/>
    <title>Rapport d'audit</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; }
        .page { padding:22px 28px; }

        .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; padding-bottom:14px; border-bottom:2px solid #e2e8f0; }
        .header-left h1 { font-size:17px; font-weight:700; color:#0f172a; }
        .header-left p  { font-size:10px; color:#64748b; margin-top:3px; }
        .header-right   { text-align:right; font-size:9px; color:#94a3b8; }

        /* Stats */
        .stats { display:table; width:100%; margin-bottom:16px; border-spacing:8px 0; }
        .stat  { display:table-cell; border-radius:8px; padding:10px 14px; border:1px solid #e2e8f0; background:#f8fafc; text-align:center; }
        .stat .val { font-size:22px; font-weight:800; color:#0f172a; }
        .stat .lbl { font-size:9px; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; margin-top:2px; }
        .stat.approved .val { color:#166534; }
        .stat.rejected .val { color:#991b1b; }
        .stat.indigo   .val { color:#4f46e5; }

        /* Filters recap */
        .filters-recap { margin-bottom:14px; font-size:9px; color:#64748b; background:#f1f5f9; border-radius:6px; padding:6px 10px; }
        .filters-recap strong { color:#334155; }

        table { width:100%; border-collapse:collapse; }
        thead tr { background:#4f46e5; }
        thead th { padding:6px 7px; text-align:left; font-size:8.5px; font-weight:700; color:#fff; text-transform:uppercase; letter-spacing:0.04em; }
        tbody tr { border-bottom:1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background:#f8fafc; }
        tbody td { padding:5px 7px; font-size:9px; color:#334155; vertical-align:top; }

        .badge { display:inline-block; padding:2px 7px; border-radius:999px; font-size:8.5px; font-weight:700; }
        .badge-approved { background:#dcfce7; color:#166534; }
        .badge-rejected { background:#fee2e2; color:#991b1b; }

        .comment { font-size:8.5px; font-style:italic; color:#64748b; margin-top:2px; }
        .footer { margin-top:14px; padding-top:8px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; font-size:8.5px; color:#94a3b8; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-left">
            <h1>Rapport d'Audit — Historique des Validations</h1>
            <p>{{ $logs->count() }} action{{ $logs->count() > 1 ? 's' : '' }} dans la période sélectionnée</p>
        </div>
        <div class="header-right">
            {{ config('app.name') }}<br>
            Généré le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('DD MMMM YYYY [à] HH:mm') }}
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat">
            <div class="val">{{ $stats['total'] }}</div>
            <div class="lbl">Total actions</div>
        </div>
        <div class="stat approved">
            <div class="val">{{ $stats['approved'] }}</div>
            <div class="lbl">Approuvées</div>
        </div>
        <div class="stat rejected">
            <div class="val">{{ $stats['rejected'] }}</div>
            <div class="lbl">Refusées</div>
        </div>
        <div class="stat indigo">
            <div class="val">{{ $stats['orders_touched'] }}</div>
            <div class="lbl">Commandes traitées</div>
        </div>
        @if($stats['total'] > 0)
        <div class="stat">
            <div class="val">{{ round($stats['approved'] / $stats['total'] * 100) }}%</div>
            <div class="lbl">Taux approbation</div>
        </div>
        @endif
    </div>

    <!-- Recap filtres -->
    @if(array_filter($filters))
    <div class="filters-recap">
        <strong>Filtres appliqués :</strong>
        @if($filters['date_from'] || $filters['date_to'])
            Période : {{ $filters['date_from'] ?: '...' }} → {{ $filters['date_to'] ?: '...' }} &nbsp;|&nbsp;
        @endif
        @if($filters['action'])
            Action : {{ $filters['action'] === 'approved' ? 'Approuvées' : 'Refusées' }} &nbsp;|&nbsp;
        @endif
        @if($filters['search'])
            Recherche : "{{ $filters['search'] }}" &nbsp;|&nbsp;
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Commande</th>
                <th>Boutique</th>
                <th>Demandeur</th>
                <th style="text-align:right;">Montant (XOF)</th>
                <th>Action</th>
                <th>Niveau</th>
                <th>Validateur</th>
                <th>Commentaire</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                <td style="max-width:110px;">{{ $log->purchaseOrder?->title ?? '—' }}</td>
                <td>{{ $log->purchaseOrder?->boutique?->name ?? '—' }}</td>
                <td>{{ $log->purchaseOrder?->user?->name ?? '—' }}</td>
                <td style="text-align:right;font-weight:600;">{{ number_format($log->purchaseOrder?->amount ?? 0, 0, ',', ' ') }}</td>
                <td><span class="badge badge-{{ $log->action }}">{{ $log->action === 'approved' ? 'Approuvée' : 'Refusée' }}</span></td>
                <td style="white-space:nowrap;">
                    @if($log->validationLevel)
                        N{{ $log->validationLevel->order }} — {{ $log->validationLevel->name }}
                    @else —
                    @endif
                </td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td style="max-width:100px;">
                    @if($log->comment)
                        <span class="comment">"{{ $log->comment }}"</span>
                    @else
                        <span style="color:#cbd5e1;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <span>Rapport d'audit &mdash; {{ config('app.name') }}</span>
        <span>{{ $logs->count() }} enregistrement{{ $logs->count() > 1 ? 's' : '' }}</span>
    </div>
</div>
</body>
</html>
