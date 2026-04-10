<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Bon de commande {{ $order->order_number ?? '#'.str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }
        .page { padding: 28px 32px; }

        /* ── Header ─────────────────────────────────────────── */
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 18px; border-bottom: 3px solid #4f46e5; margin-bottom: 20px; }
        .header-left h1 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .header-left p  { font-size: 10px; color: #64748b; margin-top: 3px; }
        .header-right   { text-align: right; }
        .bc-number      { font-size: 26px; font-weight: 800; color: #4f46e5; letter-spacing: -0.5px; }
        .bc-label       { font-size: 9px; color: #94a3b8; letter-spacing: 0.06em; text-transform: uppercase; }
        .bc-date        { font-size: 10px; color: #64748b; margin-top: 3px; }

        /* Badge statut */
        .status-badge   { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 600; margin-top: 6px; }
        .status-draft     { background: #f1f5f9; color: #475569; }
        .status-pending   { background: #fffbeb; color: #b45309; }
        .status-approved  { background: #ecfdf5; color: #065f46; }
        .status-rejected  { background: #fef2f2; color: #991b1b; }
        .delivery-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 600; margin-top: 4px; margin-left: 6px; }
        .delivery-ordered            { background: #eff6ff; color: #1d4ed8; }
        .delivery-partially_received { background: #fff7ed; color: #c2410c; }
        .delivery-received           { background: #ecfdf5; color: #065f46; }

        /* ── Layout 2 colonnes ───────────────────────────────── */
        .cols { display: table; width: 100%; border-spacing: 14px 0; margin-bottom: 18px; }
        .col  { display: table-cell; vertical-align: top; }
        .col-left  { width: 55%; }
        .col-right { width: 45%; }

        /* ── Section ─────────────────────────────────────────── */
        .section       { margin-bottom: 18px; }
        .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }

        /* Info grid */
        .info-grid  { display: table; width: 100%; }
        .info-row   { display: table-row; }
        .info-label { display: table-cell; width: 38%; font-size: 10px; color: #64748b; padding: 3px 0; vertical-align: top; }
        .info-value { display: table-cell; font-size: 11px; font-weight: 500; color: #0f172a; padding: 3px 0; vertical-align: top; }

        /* Amount */
        .amount-box       { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
        .amount-box .lbl  { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .amount-box .val  { font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        /* Description */
        .desc-box { background: #f8fafc; border-radius: 6px; padding: 8px 10px; font-size: 11px; line-height: 1.6; color: #334155; white-space: pre-wrap; }

        /* ── Lignes de commande ───────────────────────────────── */
        .lines-table { width: 100%; border-collapse: collapse; margin-bottom: 0; font-size: 10px; }
        .lines-table th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-weight: 600; color: #475569; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; }
        .lines-table td { padding: 7px 8px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: top; }
        .lines-table tr:last-child td { border-bottom: none; }
        .lines-table .right { text-align: right; }
        .lines-table .center { text-align: center; }
        .lines-table tfoot td { background: #f8fafc; font-weight: 700; border-top: 2px solid #e2e8f0; }
        .article-name { font-weight: 600; }
        .article-ref  { font-size: 9px; color: #94a3b8; font-family: monospace; }
        .article-note { font-size: 9px; color: #64748b; font-style: italic; }

        /* ── Timeline validation ──────────────────────────────── */
        .timeline      { position: relative; padding-left: 18px; }
        .timeline-item { position: relative; margin-bottom: 12px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-dot  { position: absolute; left: -18px; top: 2px; width: 12px; height: 12px; border-radius: 50%; border: 2px solid #e2e8f0; background: #fff; }
        .timeline-dot.done     { background: #10b981; border-color: #10b981; }
        .timeline-dot.active   { background: #4f46e5; border-color: #4f46e5; }
        .timeline-dot.rejected { background: #ef4444; border-color: #ef4444; }
        .timeline-line { position: absolute; left: -13px; top: 14px; width: 2px; height: calc(100% + 2px); background: #e2e8f0; }
        .timeline-item.done .timeline-line { background: #10b981; }
        .level-name      { font-weight: 600; font-size: 11px; color: #0f172a; }
        .level-meta      { font-size: 9px; color: #64748b; margin-top: 2px; }
        .level-validator { font-size: 10px; color: #4f46e5; margin-top: 2px; font-weight: 500; }
        .level-comment   { font-size: 9px; color: #64748b; font-style: italic; margin-top: 2px; background: #f1f5f9; padding: 3px 6px; border-radius: 4px; }

        /* ── Signatures ───────────────────────────────────────── */
        .signatures { display: table; width: 100%; border-spacing: 12px 0; margin-top: 24px; }
        .sig-col    { display: table-cell; width: 33.33%; vertical-align: top; }
        .sig-box    { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 12px; min-height: 70px; }
        .sig-label  { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 4px; }
        .sig-name   { font-size: 10px; font-weight: 600; color: #0f172a; margin-top: 4px; }
        .sig-date   { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        /* ── Attachments ──────────────────────────────────────── */
        .att-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .att-item:last-child { border-bottom: none; }

        /* ── Footer ───────────────────────────────────────────── */
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .footer-txt { font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
<div class="page">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            <h1>Bon de Commande Officiel</h1>
            <p>{{ $order->boutique?->name ?? 'Aucune boutique' }}@if($order->boutique) &nbsp;—&nbsp; {{ $order->boutique->code }}@endif</p>
            @if($order->fournisseur)
            <p style="margin-top:2px;">Fournisseur : <strong>{{ $order->fournisseur->name }}</strong> ({{ $order->fournisseur->code }})</p>
            @endif
            @php
                $statusLabels   = ['draft' => 'Brouillon', 'pending' => 'En attente', 'approved' => 'Approuvée', 'rejected' => 'Refusée'];
                $deliveryLabels = ['ordered' => 'Commandée', 'partially_received' => 'Reçue partiellement', 'received' => 'Reçue entièrement'];
            @endphp
            <div style="margin-top:6px;">
                <span class="status-badge status-{{ $order->status }}">
                    {{ $statusLabels[$order->status] ?? $order->status }}
                </span>
                @if($order->delivery_status)
                <span class="delivery-badge delivery-{{ $order->delivery_status }}">
                    {{ $deliveryLabels[$order->delivery_status] ?? $order->delivery_status }}
                </span>
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="bc-label">Numéro de commande</div>
            @if($order->order_number)
                <div class="bc-number">{{ $order->order_number }}</div>
            @else
                <div class="bc-number" style="color:#94a3b8;">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</div>
            @endif
            <div class="bc-date">
                Créée le {{ \Carbon\Carbon::parse($order->created_at)->locale('fr')->isoFormat('DD MMMM YYYY') }}
                @if($order->ordered_at)
                <br>Confirmée le {{ \Carbon\Carbon::parse($order->ordered_at)->locale('fr')->isoFormat('DD MMMM YYYY') }}
                @endif
            </div>
        </div>
    </div>

    {{-- ── Détails + Circuit ────────────────────────────────────────────── --}}
    <div class="cols">
        <div class="col col-left">
            <div class="section">
                <div class="section-title">Détails de la commande</div>
                <div class="amount-box">
                    <div class="lbl">Montant total</div>
                    <div class="val">{{ number_format($order->amount, 0, ',', ' ') }} XOF</div>
                </div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Demandeur</div>
                        <div class="info-value">{{ $order->user?->name ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Boutique</div>
                        <div class="info-value">{{ $order->boutique?->name ?? '—' }}</div>
                    </div>
                    @if($order->fournisseur)
                    <div class="info-row">
                        <div class="info-label">Fournisseur</div>
                        <div class="info-value">{{ $order->fournisseur->name }}</div>
                    </div>
                    @endif
                    @if($order->submitted_at)
                    <div class="info-row">
                        <div class="info-label">Soumise le</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($order->submitted_at)->locale('fr')->isoFormat('DD MMM YYYY, HH:mm') }}</div>
                    </div>
                    @endif
                    @if($order->ordered_at)
                    <div class="info-row">
                        <div class="info-label">Confirmée le</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($order->ordered_at)->locale('fr')->isoFormat('DD MMM YYYY') }}</div>
                    </div>
                    @endif
                    @if($order->fully_received_at)
                    <div class="info-row">
                        <div class="info-label">Réceptionnée le</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($order->fully_received_at)->locale('fr')->isoFormat('DD MMM YYYY') }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="section">
                <div class="section-title">Description / Objet</div>
                <div class="desc-box">{{ $order->description }}</div>
            </div>

            @if($order->attachments && $order->attachments->count())
            <div class="section">
                <div class="section-title">Pièces jointes ({{ $order->attachments->count() }})</div>
                @foreach($order->attachments as $att)
                <div class="att-item">
                    <span>{{ $att->file_name }}</span>
                    <span style="color:#94a3b8;">
                        @if($att->file_size >= 1048576) {{ round($att->file_size/1048576,1) }} MB
                        @else {{ round($att->file_size/1024) }} KB @endif
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="col col-right">
            <div class="section">
                <div class="section-title">Circuit de validation</div>
                <div class="timeline">
                    @foreach($levels as $level)
                        @php
                            $log        = $order->validationLogs->firstWhere('validation_level_id', $level->id);
                            $isDone     = $order->status === 'approved' || ($order->status === 'pending' && $order->current_level_order > $level->order);
                            $isActive   = $order->status === 'pending' && $order->current_level_order === $level->order;
                            $isRejected = $log && $log->action === 'rejected';
                            $dotClass   = $isRejected ? 'rejected' : ($isDone ? 'done' : ($isActive ? 'active' : ''));
                        @endphp
                        <div class="timeline-item {{ $isDone ? 'done' : '' }}">
                            @if(!$loop->last) <div class="timeline-line"></div> @endif
                            <div class="timeline-dot {{ $dotClass }}"></div>
                            <div class="level-name">{{ $level->name }}</div>
                            @if($isActive)
                                <div class="level-meta">En attente de validation</div>
                            @elseif($isDone && !$isRejected)
                                <div class="level-meta" style="color:#10b981;">Validé</div>
                            @elseif($isRejected)
                                <div class="level-meta" style="color:#ef4444;">Refusé</div>
                            @else
                                <div class="level-meta">En attente</div>
                            @endif
                            @if($log)
                                <div class="level-validator">{{ $log->user?->name ?? '—' }} — {{ \Carbon\Carbon::parse($log->created_at)->locale('fr')->isoFormat('DD MMM YYYY') }}</div>
                                @if($log->comment)
                                    <div class="level-comment">"{{ $log->comment }}"</div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── Lignes de commande ───────────────────────────────────────────── --}}
    @if($order->lines && $order->lines->count())
    <div class="section">
        <div class="section-title">Lignes de commande ({{ $order->lines->count() }} article(s))</div>
        <table class="lines-table">
            <thead>
                <tr>
                    <th style="width:35%">Article</th>
                    <th>Catégorie</th>
                    <th>Fournisseur</th>
                    <th class="center">Qté</th>
                    <th class="center">Unité</th>
                    <th class="right">Prix unit.</th>
                    <th class="right">Sous-total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->lines as $line)
                <tr>
                    <td>
                        <div class="article-name">{{ $line->article?->name ?? '—' }}</div>
                        @if($line->article?->reference)
                            <div class="article-ref">{{ $line->article->reference }}</div>
                        @endif
                        @if($line->note)
                            <div class="article-note">{{ $line->note }}</div>
                        @endif
                    </td>
                    <td style="font-size:10px;color:#64748b;">{{ $line->article?->category?->name ?? '—' }}</td>
                    <td style="font-size:10px;color:#64748b;">{{ $line->fournisseur?->name ?? '—' }}</td>
                    <td class="center">{{ number_format($line->quantity, 2, ',', '') }}</td>
                    <td class="center" style="color:#64748b;">{{ $line->article?->unit ?? '—' }}</td>
                    <td class="right">{{ number_format($line->unit_price, 0, ',', ' ') }}</td>
                    <td class="right" style="font-weight:600;">{{ number_format($line->quantity * $line->unit_price, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="right" style="padding-right:8px;">Total général</td>
                    <td class="right">{{ number_format($order->amount, 0, ',', ' ') }} XOF</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- ── Signatures ────────────────────────────────────────────────────── --}}
    @if($order->status === 'approved' || $order->delivery_status)
    <div class="section" style="margin-top:8px;">
        <div class="section-title">Signatures et approbations</div>
        <div class="signatures">
            <div class="sig-col">
                <div class="sig-box">
                    <div class="sig-label">Demandeur</div>
                    <div class="sig-name">{{ $order->user?->name ?? '—' }}</div>
                    @if($order->submitted_at)
                    <div class="sig-date">{{ \Carbon\Carbon::parse($order->submitted_at)->locale('fr')->isoFormat('DD MMM YYYY') }}</div>
                    @endif
                </div>
            </div>
            @foreach($levels as $level)
                @php $log = $order->validationLogs->where('action', 'approved')->firstWhere('validation_level_id', $level->id); @endphp
                <div class="sig-col">
                    <div class="sig-box">
                        <div class="sig-label">{{ $level->name }}</div>
                        @if($log)
                            <div class="sig-name">{{ $log->user?->name ?? '—' }}</div>
                            <div class="sig-date">{{ \Carbon\Carbon::parse($log->created_at)->locale('fr')->isoFormat('DD MMM YYYY') }}</div>
                        @else
                            <div style="margin-top:20px;border-top:1px solid #e2e8f0;"></div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Footer ────────────────────────────────────────────────────────── --}}
    <div class="footer">
        <div class="footer-txt">Généré le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('DD MMMM YYYY [à] HH:mm') }}</div>
        <div class="footer-txt">
            {{ $order->order_number ?? ('#'.str_pad($order->id, 5, '0', STR_PAD_LEFT)) }}
            &mdash; {{ config('app.name') }}
        </div>
    </div>

</div>
</body>
</html>
