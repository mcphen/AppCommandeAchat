<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Pièce de caisse de dépense — {{ $order->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; background: #fff; }
        .page { padding: 20px 26px; }

        /* Header */
        .header { position: relative; border-bottom: 1px solid #222; margin-bottom: 14px; padding-bottom: 10px; min-height: 70px; }
        .header-left { text-align: center; }
        .header-left .logo { position: absolute; left: 0; top: 0; max-height: 55px; max-width: 70px; object-fit: contain; display: block; }
        .header-left h1 { font-size: 16px; font-weight: bold; color: #111; margin-top: 8px; }
        .header-left .ref { font-size: 10px; color: #444; font-family: monospace; margin-top: 2px; }
        .header-right { position: absolute; right: 0; top: 0; text-align: right; font-size: 9px; color: #444; }
        .header-right .company { font-size: 11px; font-weight: bold; color: #111; }

        /* Info sections */
        .section { margin-bottom: 12px; }
        .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.07em; color: #111; margin-bottom: 6px; border-bottom: 1px solid #cfcfcf; padding-bottom: 3px; }

        /* Info grid */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 3px 6px; font-size: 9px; vertical-align: top; }
        .info-table td.lbl { color: #555; width: 35%; white-space: nowrap; }
        .info-table td.val { font-weight: 600; color: #111; }

        /* Amount */
        .amount-box { border: 1px solid #111; padding: 10px 12px; margin-top: 14px; margin-bottom: 14px; }
        .amount-box .lbl { font-size: 9px; color: #111; text-transform: uppercase; letter-spacing: 0.05em; }
        .amount-box .val { font-size: 18px; font-weight: 800; color: #111; margin-top: 4px; }
        .amount-box .words { font-size: 9px; color: #333; margin-top: 4px; font-style: italic; }
        .amount-box .nature { margin-top: 6px; font-size: 9px; color: #222; }

        /* Description */
        .desc-box { border: 1px solid #cfcfcf; padding: 7px 10px; min-height: 52px; font-size: 9px; color: #111; line-height: 1.5; }

        /* Simple text blocks */
        .note-box { border: 1px solid #cfcfcf; padding: 7px 10px; min-height: 28px; color: #222; line-height: 1.5; }
        .history-list { margin-top: 6px; padding-left: 14px; }
        .history-list li { margin-bottom: 4px; color: #222; }
        .history-muted { color: #555; }
        .attachments-list { margin: 0; padding-left: 14px; }
        .attachments-list li { margin-bottom: 3px; color: #111; }

        /* Signatures */
        .sig-section { margin-top: 24px; }
        .sig-table { width: 100%; border-collapse: collapse; }
        .sig-table td { text-align: center; width: 33.33%; padding: 0 8px; vertical-align: top; }
        .sig-box { border-top: 1px solid #111; margin-top: 28px; padding-top: 4px; font-size: 9px; color: #111; font-weight: bold; }

        /* Footer */
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #cfcfcf; display: flex; justify-content: space-between; font-size: 8px; color: #555; }

        /* Stamp area */
        .stamp-area { border: 1px dashed #999; height: 50px; margin-top: 4px; }
    </style>
</head>
<body>
<div class="page">

@php
    $companyName    = $company['company_name']       ?? config('app.name');
    $companyAddress = $company['company_address']    ?? '';
    $companyPhone   = $company['company_phone']      ?? '';
    $companyEmail   = $company['company_email']      ?? '';
    $companyNif     = $company['company_nif']        ?? '';

    $statusLabels = [
        'draft'          => 'Brouillon',
        'pending'        => 'En attente',
        'needs_revision' => 'Révision demandée',
        'approved'       => 'Approuvée',
        'rejected'       => 'Refusée',
        'cancelled'      => 'Annulée',
    ];

    // Montant en lettres
    if (!function_exists('_dd_numToFr')) {
        function _dd_numToFr(int $n): string {
            if ($n === 0) return 'zéro';
            $units = ['','un','deux','trois','quatre','cinq','six','sept','huit','neuf',
                      'dix','onze','douze','treize','quatorze','quinze','seize',
                      'dix-sept','dix-huit','dix-neuf'];
            $tens  = ['','','vingt','trente','quarante','cinquante','soixante',
                      'soixante','quatre-vingt','quatre-vingt'];
            $res = '';
            if ($n < 0) return 'moins '._dd_numToFr(-$n);
            if ($n >= 1000000) {
                $m = intdiv($n, 1000000);
                $res .= _dd_numToFr($m).($m > 1 ? ' millions' : ' million');
                $n %= 1000000;
                if ($n) $res .= ' ';
            }
            if ($n >= 1000) {
                $k = intdiv($n, 1000);
                $res .= ($k === 1 ? 'mille' : _dd_numToFr($k).' mille');
                $n %= 1000;
                if ($n) $res .= ' ';
            }
            if ($n >= 100) {
                $h = intdiv($n, 100);
                if ($h === 1) $res .= 'cent';
                else $res .= $units[$h].' cent';
                $n %= 100;
                if ($n) $res .= ' ';
                elseif ($h > 1) $res .= 's';
            }
            if ($n > 0) {
                if ($n < 20) {
                    $res .= $units[$n];
                } elseif ($n < 70) {
                    $t = intdiv($n, 10); $u = $n % 10;
                    $res .= $tens[$t];
                    if ($u === 1) $res .= ' et un';
                    elseif ($u) $res .= '-'.$units[$u];
                    elseif ($t === 8) $res .= 's';
                } elseif ($n < 80) {
                    $u = $n - 60;
                    $res .= 'soixante'.($u === 11 ? ' et' : '').'-'.$units[$u];
                } elseif ($n < 90) {
                    $u = $n - 80;
                    $res .= 'quatre-vingt'.($u ? '-'.$units[$u] : 's');
                } else {
                    $u = $n - 80;
                    $res .= 'quatre-vingt-'.$units[$u];
                }
            }
            return $res;
        }
    }

    $amountInt   = (int) round((float) $order->amount);
    $amountFmt   = number_format($amountInt, 0, ',', ' ');
    $amountWords = ucfirst(_dd_numToFr($amountInt)) . ' Francs CFA';
@endphp

{{-- ──────────── HEADER ──────────── --}}
<div class="header">
    <div class="header-left">
        @if(!empty($logoB64))
            <img src="{{ $logoB64 }}" alt="{{ $companyName }}" class="logo" />
        @endif
        <h1>Pièce de caisse de dépense</h1>
        <div class="ref">{{ $order->reference }}</div>
    </div>
    <div class="header-right">
        <div class="company">{{ $companyName }}</div>
        @if($companyAddress)<div>{{ $companyAddress }}</div>@endif
        @if($companyPhone)<div>Tél : {{ $companyPhone }}</div>@endif
        @if($companyEmail)<div>{{ $companyEmail }}</div>@endif
        @if($companyNif)<div>NINEA : {{ $companyNif }}</div>@endif
        <div style="margin-top:4px; color:#555;">Généré le {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

{{-- ──────────── INFORMATIONS ──────────── --}}
<div class="section">
    <div class="section-title">Informations générales</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Référence</td>
            <td class="val">{{ $order->reference }}</td>
            <td class="lbl">Statut</td>
            <td class="val">{{ $statusLabels[$order->status] ?? $order->status }}</td>
        </tr>
        <tr>
            <td class="lbl">Désignation</td>
            <td class="val" colspan="3">{{ $order->title }}</td>
        </tr>
        <tr>
            <td class="lbl">Nature de l'opération</td>
            <td class="val" colspan="3">{{ $order->natureOperation?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Date de création</td>
            <td class="val">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</td>
            <td class="lbl">Soumis le</td>
            <td class="val">{{ $order->submitted_at ? \Carbon\Carbon::parse($order->submitted_at)->format('d/m/Y') : '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Demandeur</td>
            <td class="val">{{ $order->user?->name ?? '—' }}</td>
            <td class="lbl">Boutique</td>
            <td class="val">{{ $order->boutique?->name ?? '—' }}</td>
        </tr>
    </table>
</div>

{{-- ──────────── JUSTIFICATION ──────────── --}}
@if($order->description)
<div class="section">
    <div class="section-title">Justification</div>
    <div class="desc-box">{{ $order->description }}</div>
</div>
@endif

{{-- ──────────── VALIDATION ──────────── --}}
<div class="section">
    <div class="section-title">Validation</div>
    @if($order->status === 'draft')
        <div class="note-box">Non encore soumise à validation.</div>
    @else
        <div class="note-box">
            <div><strong>Statut actuel :</strong> {{ $statusLabels[$order->status] ?? $order->status }}</div>
            @if($order->submitted_at)
                <div class="history-muted" style="margin-top:4px;">Soumise le {{ \Carbon\Carbon::parse($order->submitted_at)->format('d/m/Y') }}</div>
            @endif
            @if($order->validationLogs->count())
                <ol class="history-list">
                    @foreach($order->validationLogs->sortBy('created_at') as $log)
                        <li>
                            <strong>{{ $log->validationLevel?->name ?? 'Niveau' }}</strong>
                            : {{ $log->action === 'approved' ? 'Approuvé' : 'Refusé' }}
                            @if($log->user?->name) par {{ $log->user->name }} @endif
                            le {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y') }}
                            @if($log->comment) - {{ $log->comment }} @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @endif
</div>

{{-- ──────────── PIÈCES JOINTES ──────────── --}}
@if($order->attachments && $order->attachments->count())
<div class="section">
    <div class="section-title">Pièces jointes ({{ $order->attachments->count() }})</div>
    <ul class="attachments-list">
        @foreach($order->attachments as $att)
        <li>
            {{ $att->file_name }}
            <span class="history-muted">
                @php
                    $sz = $att->file_size;
                    echo $sz < 1024 ? "{$sz} o" : ($sz < 1048576 ? round($sz/1024,1).' Ko' : round($sz/1048576,1).' Mo');
                @endphp
            </span>
        </li>
        @endforeach
    </ul>
</div>
@endif

{{-- ──────────── MONTANT ──────────── --}}
<div class="amount-box">
    <div class="lbl">Montant demandé</div>
    <div class="val">{{ $amountFmt }} XOF</div>
    <div class="words">Arrêté la présente pièce de caisse à la somme de : {{ $amountWords }}</div>
    @if($order->natureOperation)
        <div class="nature"><strong>Nature :</strong> {{ $order->natureOperation->name }}</div>
    @endif
</div>

{{-- ──────────── SIGNATURES ──────────── --}}
<div class="sig-section">
    <table class="sig-table">
        <tr>
            <td>
                <div style="font-size:9px; color:#111; text-align:center; font-weight:bold;">LE DEMANDEUR</div>
                <div class="stamp-area"></div>
                <div class="sig-box">{{ $order->user?->name ?? '—' }}</div>
            </td>
            <td>
                <div style="font-size:9px; color:#111; text-align:center; font-weight:bold;">VISA HIÉRARCHIQUE</div>
                <div class="stamp-area"></div>
                <div class="sig-box">&nbsp;</div>
            </td>
            <td>
                <div style="font-size:9px; color:#111; text-align:center; font-weight:bold;">LE CAISSIER / COMPTABLE</div>
                <div class="stamp-area"></div>
                <div class="sig-box">&nbsp;</div>
            </td>
        </tr>
    </table>
</div>

{{-- ──────────── FOOTER ──────────── --}}
<div class="footer">
    <span>{{ $companyName }} — Pièce de caisse de dépense {{ $order->reference }}</span>
    <span>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</span>
</div>

</div>
</body>
</html>
