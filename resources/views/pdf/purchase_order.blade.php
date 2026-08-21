<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Bon de commande {{ $order->order_number ?? '#'.str_pad($order->id, 5, '0', STR_PAD_LEFT) }} — {{ $company['company_name'] ?? config('app.name') }}</title>
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
        .col-left  { width: 100%; }

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

        /* ── Signatures ───────────────────────────────────────── */
        .signatures  { display: table; width: 100%; border-spacing: 12px 0; margin-top: 24px; }
        .sig-col     { display: table-cell; width: 33.33%; vertical-align: top; }
        .sig-box     { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 12px; min-height: 80px; }
        .sig-label   { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 4px; }
        .sig-image   { max-height: 40px; max-width: 120px; object-fit: contain; display: block; margin: 4px 0; }
        .sig-name    { font-size: 10px; font-weight: 600; color: #0f172a; margin-top: 4px; }
        .sig-date    { font-size: 9px; color: #94a3b8; margin-top: 2px; }
        .sig-pending { margin-top: 28px; border-top: 1px solid #e2e8f0; }

        /* ── Cachet d'approbation (ex: DGA) — en haut a droite, au-dessus du numero ── */
        .approval-stamp      { text-align: right; margin-bottom: 8px; }
        .approval-stamp .ok  { display:inline-block; border:3px solid #059669; color:#059669; font-size:18px; font-weight:800; letter-spacing:0.12em; padding:3px 14px; border-radius:6px; transform:rotate(-6deg); }
        .approval-stamp .who { margin-top:3px; font-size:8px; color:#059669; font-weight:600; }

        /* ── Attachments ──────────────────────────────────────── */
        .att-item { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        .att-item:last-child { border-bottom: none; }

        /* ── Footer ───────────────────────────────────────────── */
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .footer-txt { font-size: 9px; color: #94a3b8; }
    </style>
</head>
@php
    $companyName    = $company['company_name']    ?? config('app.name');
    $companyAddress = $company['company_address'] ?? null;
    $companyPhone   = $company['company_phone']   ?? null;
    $companyEmail   = $company['company_email']   ?? null;
    $companyNif     = $company['company_nif']     ?? null;
    $companyRccm    = $company['company_rccm']    ?? null;

    // Le niveau de type "approbation" (ex: DGA) est affiche a part, comme un
    // cachet en haut a droite, plutot que dans la rangee de signatures du bas.
    $approvalLevel = $levels->first(fn ($l) => $l->isApproval());
    $approvalLog   = $approvalLevel
        ? $order->validationLogs->where('action', 'approved')->firstWhere('validation_level_id', $approvalLevel->id)
        : null;
    $signatureLevels = $levels->reject(fn ($l) => $l->isApproval());
@endphp
<body>
<div class="page">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            {{-- Logo entreprise --}}
            @if(!empty($logoB64))
            <img src="{{ $logoB64 }}" alt="{{ $companyName }}" style="max-height:80px; max-width:260px; object-fit:contain; margin-bottom:6px; display:block;" />
            @endif
           
            
            
            <p style="margin-top:6px; font-size:10px; color:#64748b;">
                {{ $order->boutique?->name ?? 'Aucune boutique' }}@if($order->boutique) &nbsp;—&nbsp; {{ $order->boutique->code }}@endif
            </p>
            @if($order->fournisseur)
            <p style="margin-top:2px;">Fournisseur : <strong>{{ $order->fournisseur->name }}</strong> ({{ $order->fournisseur->code }})</p>
            @endif
            @if($order->project)
            <p style="margin-top:2px;">Chantier : <strong>{{ $order->project->name }}</strong></p>
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
            @if($approvalLog)
            <div class="approval-stamp">
                <div class="ok">OK</div>
                <div class="who">{{ $approvalLevel->name }} — {{ $approvalLog->user?->name ?? '—' }}<br>{{ \Carbon\Carbon::parse($approvalLog->created_at)->locale('fr')->isoFormat('DD MMM YYYY') }}</div>
            </div>
            @endif
            <div class="bc-label">Numéro de commande</div>
            @if($order->title)
                <div class="bc-number">{{ $order->title }}</div>
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
                    <div class="lbl">Montant total HT</div>
                    <div class="val">{{ number_format($order->amount, 0, ',', ' ') }} XOF</div>
                    @if($order->amount_ttc)
                    <div class="lbl" style="margin-top:8px;">Montant total TTC</div>
                    <div class="val">{{ number_format($order->amount_ttc, 0, ',', ' ') }} XOF</div>
                    @endif
                </div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Demandeur</div>
                        <div class="info-value">{{ $order->user?->name ?? '—' }}</div>
                    </div>
                    @if($order->project)
                    <div class="info-row">
                        <div class="info-label">Chantier</div>
                        <div class="info-value">{{ $order->project->name }}</div>
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
    </div>

    {{-- ── Lignes de commande ───────────────────────────────────────────── --}}
    @if($order->lines && $order->lines->count())
    <div class="section">
        <div class="section-title">Lignes de commande ({{ $order->lines->count() }} article(s))</div>
        <table class="lines-table">
            <thead>
                <tr>
                    <th style="width:45%">Article</th>
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
                    <td class="center">{{ number_format($line->quantity, 2, ',', '') }}</td>
                    <td class="center" style="color:#64748b;">{{ $line->article?->unit ?? '—' }}</td>
                    <td class="right">{{ number_format($line->unit_price, 0, ',', ' ') }}</td>
                    <td class="right" style="font-weight:600;">{{ number_format($line->quantity * $line->unit_price, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right" style="padding-right:8px;">Total général HT</td>
                    <td class="right">{{ number_format($order->amount, 0, ',', ' ') }} XOF</td>
                </tr>
                @if($order->amount_ttc)
                <tr>
                    <td colspan="4" class="right" style="padding-right:8px;">Total général TTC</td>
                    <td class="right">{{ number_format($order->amount_ttc, 0, ',', ' ') }} XOF</td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
    @endif

    {{-- ── Signatures ────────────────────────────────────────────────────── --}}
    @if($signatureLevels->isNotEmpty() && ($order->status !== 'draft'))
    <div class="section" style="margin-top:8px;">
        <div class="section-title">Signatures et approbations</div>
        <div class="signatures">
            @foreach($signatureLevels as $level)
                @php
                    $log = $order->validationLogs->where('action', 'approved')->firstWhere('validation_level_id', $level->id);
                    $sigImg = null;
                    if ($log && $log->user?->signature_path) {
                        $absPath = storage_path('app/public/' . $log->user->signature_path);
                        if (file_exists($absPath)) {
                            $mime   = mime_content_type($absPath);
                            $sigImg = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
                        }
                    }
                @endphp
                <div class="sig-col">
                    <div class="sig-box">
                        <div class="sig-label">{{ $level->name }}</div>
                        @if($log)
                            @if($sigImg)
                                <img src="{{ $sigImg }}" class="sig-image" alt="Signature {{ $log->user?->name }}" />
                            @endif
                            <div class="sig-name">{{ $log->user?->name ?? '—' }}</div>
                            <div class="sig-date">{{ \Carbon\Carbon::parse($log->created_at)->locale('fr')->isoFormat('DD MMM YYYY') }}</div>
                        @else
                            <div class="sig-pending"></div>
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
            &mdash; {{ $company['company_name'] ?? config('app.name') }}
        </div>
    </div>

</div>
</body>
</html>
