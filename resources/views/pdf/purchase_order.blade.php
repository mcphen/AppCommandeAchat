<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Bon de commande</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; background: #fff; }
        .page { padding: 18px 24px; }

        /* Underlined info lines */
        .info-line { border-bottom: 1px dotted #888; display: block; min-height: 13px; padding: 1px 0 1px 2px; margin-bottom: 3px; }

        /* Supplier bordered box */
        .supplier-box { border: 1px solid #555; padding: 7px 10px; font-size: 9px; }
        .supplier-box .sline { border-bottom: 1px dotted #888; min-height: 13px; padding: 1px 2px; margin-bottom: 2px; }
        .supplier-box .sline:last-child { border-bottom: none; margin-bottom: 0; }

        /* "Bon de Commande" */
        .bc-title { font-size: 24px; font-weight: bold; margin: 10px 0 4px 0; }

        /* Badge-like buttons */
        .badge-btn { background: #d8d8d8; padding: 3px 10px; font-size: 9px; display: inline-block; }
        .badge-val  { border: 1px solid #888; padding: 3px 10px; font-size: 9px; background: #fff; display: inline-block; }

        /* Delivery / ref box */
        .deliv-box { border: 1px solid #aaa; background: #f0f0f0; padding: 6px 10px; font-size: 9px; line-height: 1.8; }
        .deliv-box .dline { border-bottom: 1px dotted #999; min-height: 13px; padding: 1px 2px; margin-bottom: 2px; }
        .deliv-box .dline:last-child { border-bottom: none; margin-bottom: 0; }

        /* Lines table */
        .lines-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 8px; }
        .lines-table th { border: 1px solid #888; background: #e8e8e8; padding: 4px 5px; text-align: center; font-weight: bold; }
        .lines-table td { border: 1px solid #bbb; padding: 4px 5px; vertical-align: top; }
        .lines-table td.right { text-align: right; }
        .lines-table td.center { text-align: center; }

        /* Total words */
        .total-words-box { border: 1px dashed #aaa; padding: 5px 8px; font-size: 9px; min-height: 28px; }

        /* Totals right box */
        .totals-box { border: 1px solid #aaa; border-collapse: collapse; width: 100%; font-size: 9px; }
        .totals-box td { padding: 4px 7px; border-bottom: 1px solid #ddd; }
        .totals-box td.lbl { color: #333; }
        .totals-box td.val { text-align: right; font-weight: 600; white-space: nowrap; }
        .totals-box tr:last-child td { border-bottom: none; }

        /* Signatures */
        .sig-table { width: 100%; border-collapse: collapse; }
        .sig-table td { text-align: center; width: 33.33%; vertical-align: top; font-size: 10px; font-weight: bold; padding-top: 4px; }
        .sig-table td span { text-decoration: underline; }
    </style>
</head>
<body>
<div class="page">

@php
    $companyName    = $company['company_name']       ?? config('app.name');
    $companyAddress = $company['company_address']    ?? '';
    $companyComplement = $company['company_complement'] ?? '';
    $companyEmail   = $company['company_email']      ?? '';
    $companyPhone   = $company['company_phone']      ?? '';
    $companyFax     = $company['company_fax']        ?? '';
    $companyNif     = $company['company_nif']        ?? '';
    $companyRccm    = $company['company_rccm']       ?? '';

    $orderNumber = $order->order_number ?? ('#'.str_pad($order->id, 5, '0', STR_PAD_LEFT));
    $orderDate   = $order->ordered_at
        ? \Carbon\Carbon::parse($order->ordered_at)->format('d/m/Y')
        : \Carbon\Carbon::parse($order->created_at)->format('d/m/Y');

    $fournisseur = $order->fournisseur;

    // Totals
    $totalAmount = 0;
    if ($order->lines && $order->lines->count()) {
        foreach ($order->lines as $ln) {
            $discount = isset($ln->discount) ? (float)$ln->discount : 0;
            $totalAmount += $ln->quantity * $ln->unit_price * (1 - $discount / 100);
        }
    } else {
        $totalAmount = (float)($order->amount ?? 0);
    }
    $hasLines = $order->lines && $order->lines->count() > 0;

    // Conversion montant en lettres (français)
    if (!function_exists('_pdf_numToFr')) {
        function _pdf_numToFr(int $n): string {
            if ($n === 0) return 'zéro';
            $units = ['','un','deux','trois','quatre','cinq','six','sept','huit','neuf',
                      'dix','onze','douze','treize','quatorze','quinze','seize',
                      'dix-sept','dix-huit','dix-neuf'];
            $tens  = ['','','vingt','trente','quarante','cinquante','soixante',
                      'soixante','quatre-vingt','quatre-vingt'];
            $res = '';
            if ($n < 0) { return 'moins '._pdf_numToFr(-$n); }
            if ($n >= 1000000000) {
                $b = intdiv($n, 1000000000);
                $res .= _pdf_numToFr($b).($b > 1 ? ' milliards' : ' milliard');
                $n %= 1000000000;
                if ($n) $res .= ' ';
            }
            if ($n >= 1000000) {
                $m = intdiv($n, 1000000);
                $res .= _pdf_numToFr($m).($m > 1 ? ' millions' : ' million');
                $n %= 1000000;
                if ($n) $res .= ' ';
            }
            if ($n >= 1000) {
                $k = intdiv($n, 1000);
                $res .= ($k === 1 ? 'mille' : _pdf_numToFr($k).' mille');
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

    $amountInt  = (int) round($totalAmount);
    $amountWords = ucfirst(_pdf_numToFr($amountInt)).' Francs CFA';
@endphp

{{-- ══════════════════════════════════════════════════════════
     TOP HEADER — logo + infos société  |  date/ref + fournisseur
═══════════════════════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
    <tr>
        {{-- LEFT : logo + adresses + titre --}}
        <td style="width:44%; vertical-align:top; padding-right:12px;">

            @if(!empty($logoB64))
            <img src="{{ $logoB64 }}" alt="{{ $companyName }}"
                 style="max-height:65px; max-width:75px; object-fit:contain; display:block; margin-bottom:6px;" />
            @endif

            <span class="info-line">{{ $companyAddress }}</span>
            <span class="info-line">{{ $companyComplement }}</span>
            <span class="info-line">{{ $companyEmail }}</span>

            <table style="width:100%; border-collapse:collapse; font-size:9px; margin-bottom:1px;">
                <tr>
                    <td style="width:28%; white-space:nowrap;">Téléphone :</td>
                    <td><span class="info-line">{{ $companyPhone }}</span></td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;">Télécopie :</td>
                    <td><span class="info-line">{{ $companyFax }}</span></td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;">NINEA &nbsp;&nbsp;&nbsp;&nbsp;:</td>
                    <td><span class="info-line">{{ $companyNif }}</span></td>
                </tr>
                <tr>
                    <td style="white-space:nowrap;">RCOM &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</td>
                    <td><span class="info-line">{{ $companyRccm }}</span></td>
                </tr>
            </table>

            <div class="bc-title">Bon de Commande</div>
        </td>

        {{-- RIGHT : date/n° achat + bloc fournisseur --}}
        <td style="width:56%; vertical-align:top;">

            {{-- Date & N° achat --}}
            <table style="width:100%; border-collapse:collapse; margin-bottom:10px;">
                <tr>
                    <td style="text-align:right; padding-bottom:4px;">
                        <span class="badge-btn">Date</span>
                        <span class="badge-val" style="min-width:90px;">{{ $orderDate }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:right;">
                        <span class="badge-btn">Achat :</span>
                        <span class="badge-val" style="min-width:130px;">{{ $orderNumber }}</span>
                    </td>
                </tr>
            </table>

            {{-- Supplier box --}}
            <div class="supplier-box">
                <div class="sline"><strong>{{ $fournisseur?->name ?? '—' }}</strong></div>
                <div class="sline">{{ $fournisseur?->contact ?? '' }}</div>
                <div class="sline">{{ $fournisseur?->address ?? '' }}</div>
                <div class="sline">{{ $fournisseur?->complement ?? '' }}</div>
                <div class="sline">{{ $fournisseur?->phone ?? $fournisseur?->fax ?? '' }}</div>
            </div>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════════════
     SUIVI PAR  +  ADRESSE DE LIVRAISON
═══════════════════════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:6px;">
    <tr>
        <td style="width:44%; vertical-align:middle; padding-right:12px;">
            <span class="badge-btn">Suivi Par :</span>
            <span class="badge-val" style="min-width:110px;">{{ $order->user?->name ?? '—' }}</span>
        </td>
        <td style="width:56%; vertical-align:middle; text-align:right;">
            <span class="badge-btn" style="padding:4px 20px;">Adresse de livraison</span>
        </td>
    </tr>
</table>

{{-- Delivery / reference box --}}
<div class="deliv-box" style="margin-bottom:10px;">
    <div class="dline">
        {{ $order->boutique?->name ?? '' }}
        @if($order->boutique) &nbsp;—&nbsp; {{ $order->boutique->code }} @endif
    </div>
    <div class="dline">{{ $companyAddress }}</div>
    <div class="dline">{{ $companyPhone }}</div>
</div>

{{-- ══ TITRE DE LA COMMANDE (toujours affiché) ══ --}}
<div style="border: 1px solid #ccc; background:#f8f8f8; padding: 6px 10px; margin-bottom:10px; font-size:10px;">
    <strong>Objet :</strong> {{ $order->title }}
    @if($order->description)
        <div style="color:#555; font-size:9px; margin-top:2px;">{{ $order->description }}</div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════
     LIGNES DE COMMANDE (seulement si lignes présentes)
═══════════════════════════════════════════════════════════════ --}}
@if($hasLines)
<table class="lines-table">
    <thead>
        <tr>
            <th style="width:13%">Référence</th>
            <th style="width:37%">Désignation</th>
            <th style="width:9%">Qté</th>
            <th style="width:14%">Px unitaire</th>
            <th style="width:9%">Remise</th>
            <th style="width:18%">Montant</th>
        </tr>
    </thead>
    <tbody>
        @php $lineCount = $order->lines->count(); @endphp

        @foreach($order->lines as $line)
        @php
            $discount  = isset($line->discount) ? (float)$line->discount : 0;
            $lineTotal = $line->quantity * $line->unit_price * (1 - $discount / 100);
        @endphp
        <tr>
            <td>{{ $line->article?->reference ?? '—' }}</td>
            <td>
                <strong>{{ $line->article?->name ?? '—' }}</strong>
                @if($line->note)
                    <br/><em style="color:#666; font-size:8px;">{{ $line->note }}</em>
                @endif
            </td>
            <td class="center">{{ number_format($line->quantity, 2, ',', '') }}</td>
            <td class="right">{{ number_format($line->unit_price, 0, ',', ' ') }}</td>
            <td class="center">{{ $discount > 0 ? number_format($discount, 1, ',', '').' %' : '—' }}</td>
            <td class="right">{{ number_format($lineTotal, 0, ',', ' ') }}</td>
        </tr>
        @endforeach

        {{-- Filler rows to reach at least 5 lines --}}
        @for($i = $lineCount; $i < 5; $i++)
        <tr>
            <td style="height:16px;">&nbsp;</td>
            <td></td><td></td><td></td><td></td><td></td>
        </tr>
        @endfor
    </tbody>
</table>
@endif

{{-- ══════════════════════════════════════════════════════════
     TOTAUX
═══════════════════════════════════════════════════════════════ --}}
<table style="width:100%; border-collapse:collapse; margin-top:8px;">
    <tr>
        {{-- Total en lettres --}}
        <td style="vertical-align:top; padding-right:8px;">
            <div class="total-words-box" style="margin-bottom:5px; font-style:italic; color:#555;">
                Arrêter la présente Commande à la somme de :
            </div>
            <div class="total-words-box">
                @if(!empty($order->amount_in_words))
                    {{ $order->amount_in_words }}
                @else
                    {{ $amountWords }}
                @endif
            </div>
        </td>

        {{-- Montant total --}}
        <td style="width:30%; vertical-align:top; white-space:nowrap;">
            <table class="totals-box">
                <tr>
                    <td class="lbl"><strong>Montant total</strong></td>
                    <td class="val"><strong>{{ number_format($totalAmount, 0, ',', ' ') }}</strong></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ══════════════════════════════════════════════════════════
     SIGNATURES — Demandeur + niveaux de validation dynamiques
═══════════════════════════════════════════════════════════════ --}}
@php
    $sigCols = collect();

    // Colonne Demandeur
    $sigCols->push([
        'label'   => 'Demandeur',
        'name'    => $order->user?->name ?? '—',
        'date'    => $order->submitted_at
                        ? \Carbon\Carbon::parse($order->submitted_at)->locale('fr')->isoFormat('DD MMM YYYY')
                        : null,
        'img'     => null,
        'pending' => false,
    ]);

    // Une colonne par niveau de validation
    foreach ($levels as $level) {
        $log = $order->validationLogs
            ->where('action', 'approved')
            ->firstWhere('validation_level_id', $level->id);

        $sigImg = null;
        if ($log && $log->user?->signature_path) {
            $absPath = storage_path('app/public/' . $log->user->signature_path);
            if (file_exists($absPath)) {
                $mime   = mime_content_type($absPath);
                $sigImg = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
            }
        }

        $sigCols->push([
            'label'   => $level->name,
            'name'    => $log ? ($log->user?->name ?? '—') : null,
            'date'    => $log ? \Carbon\Carbon::parse($log->created_at)->locale('fr')->isoFormat('DD MMM YYYY') : null,
            'img'     => $sigImg,
            'pending' => !$log,
        ]);
    }

    $colPct = floor(100 / $sigCols->count());
@endphp

<table style="width:100%; border-collapse:collapse; margin-top:38px;">
    {{-- Labels --}}
    <tr>
        @foreach($sigCols as $col)
        <td style="width:{{ $colPct }}%; text-align:center; font-size:10px; font-weight:bold; padding-bottom:4px;">
            <span style="text-decoration:underline;">{{ $col['label'] }}</span>
        </td>
        @endforeach
    </tr>
    {{-- Espace signature + image + nom --}}
    <tr>
        @foreach($sigCols as $col)
        <td style="width:{{ $colPct }}%; text-align:center; vertical-align:bottom; border-top:1px solid #bbb; padding-top:8px; height:70px;">
            @if($col['img'])
                <img src="{{ $col['img'] }}" style="max-height:40px; max-width:90px; object-fit:contain; display:block; margin:0 auto 4px;" />
            @endif
            @if($col['name'])
                <div style="font-size:9px; font-weight:600; color:#222;">{{ $col['name'] }}</div>
            @endif
            @if($col['date'])
                <div style="font-size:8px; color:#888; margin-top:2px;">{{ $col['date'] }}</div>
            @endif
            @if($col['pending'])
                <div style="font-size:8px; color:#aaa; margin-top:16px; font-style:italic;">En attente</div>
            @endif
        </td>
        @endforeach
    </tr>
</table>

</div>
</body>
</html>
