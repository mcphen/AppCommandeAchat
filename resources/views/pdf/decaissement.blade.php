<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Reçu de décaissement</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; background: #fff; }
        .page { padding: 18px 24px; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { vertical-align: top; }

        .company-name { font-size: 14px; font-weight: bold; }
        .company-info { font-size: 8px; color: #444; line-height: 1.6; margin-top: 2px; }

        .doc-title { font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; text-align: right; }
        .doc-subtitle { text-align: right; font-size: 9px; color: #555; margin-top: 3px; }

        hr.divider { border: none; border-top: 2px solid #222; margin: 8px 0; }
        hr.thin     { border: none; border-top: 1px solid #ccc; margin: 6px 0; }

        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info-grid td { padding: 3px 5px; vertical-align: top; font-size: 9px; }
        .info-grid .lbl { color: #555; width: 35%; }
        .info-grid .val { font-weight: 600; }

        .section-title {
            background: #222;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
            padding: 4px 8px;
            letter-spacing: 0.5px;
            margin: 10px 0 4px 0;
            text-transform: uppercase;
        }

        .dec-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .dec-table th { border: 1px solid #888; background: #e8e8e8; padding: 4px 6px; text-align: center; font-weight: bold; }
        .dec-table td { border: 1px solid #ccc; padding: 4px 6px; vertical-align: top; }
        .dec-table td.right { text-align: right; }
        .dec-table td.center { text-align: center; }
        .dec-table tr.total-row td { background: #f0f0f0; font-weight: bold; border-top: 2px solid #888; }

        .total-words-box { border: 1px dashed #aaa; padding: 5px 8px; font-size: 9px; min-height: 28px; margin-top: 8px; }
        .total-words-label { font-style: italic; color: #555; margin-bottom: 3px; }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-paid    { background: #d1fae5; color: #065f46; }
        .badge-partial { background: #fef3c7; color: #92400e; }

        .sig-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .sig-table td { text-align: center; width: 33.33%; vertical-align: top; font-size: 10px; font-weight: bold; padding-top: 4px; }
        .sig-table td span { text-decoration: underline; }
        .sig-box { border: 1px solid #ccc; min-height: 50px; margin: 6px 8px 0 8px; }

        .footer { text-align: center; font-size: 7.5px; color: #888; margin-top: 18px; border-top: 1px solid #ddd; padding-top: 5px; }
    </style>
</head>
<body>
<div class="page">

@php
    $companyName       = $company['company_name']       ?? config('app.name');
    $companyAddress    = $company['company_address']    ?? '';
    $companyPhone      = $company['company_phone']      ?? '';
    $companyEmail      = $company['company_email']      ?? '';
    $companyNif        = $company['company_nif']        ?? '';
    $companyRccm       = $company['company_rccm']       ?? '';

    $totalDecaisse  = $order->decaissements->sum('montant');
    $resteADecaisser = max(0, (float)$order->amount - (float)$totalDecaisse);
    $isPaid          = $order->payment_status === 'paid';

    // Montant total en lettres
    if (!function_exists('_dec_numToFr')) {
        function _dec_numToFr(int $n): string {
            if ($n === 0) return 'zéro';
            $units = ['','un','deux','trois','quatre','cinq','six','sept','huit','neuf',
                      'dix','onze','douze','treize','quatorze','quinze','seize',
                      'dix-sept','dix-huit','dix-neuf'];
            $tens  = ['','','vingt','trente','quarante','cinquante','soixante',
                      'soixante','quatre-vingt','quatre-vingt'];
            $res = '';
            if ($n < 0) return 'moins '._dec_numToFr(-$n);
            if ($n >= 1000000000) {
                $b = intdiv($n, 1000000000);
                $res .= _dec_numToFr($b).($b > 1 ? ' milliards' : ' milliard');
                $n %= 1000000000; if ($n) $res .= ' ';
            }
            if ($n >= 1000000) {
                $m = intdiv($n, 1000000);
                $res .= _dec_numToFr($m).($m > 1 ? ' millions' : ' million');
                $n %= 1000000; if ($n) $res .= ' ';
            }
            if ($n >= 1000) {
                $k = intdiv($n, 1000);
                $res .= ($k === 1 ? 'mille' : _dec_numToFr($k).' mille');
                $n %= 1000; if ($n) $res .= ' ';
            }
            if ($n >= 100) {
                $h = intdiv($n, 100);
                $res .= ($h === 1 ? 'cent' : $units[$h].' cent');
                $n %= 100;
                if ($n) $res .= ' '; elseif ($h > 1) $res .= 's';
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
                    $res .= 'quatre-vingt-'.$units[$n - 80];
                }
            }
            return $res;
        }
    }

    $totalWords = ucfirst(_dec_numToFr((int) round($totalDecaisse))).' Francs CFA';
@endphp

{{-- ══ EN-TÊTE ══ --}}
<table class="header-table">
    <tr>
        <td style="width:55%">
            @if($logoB64)
                <img src="{{ $logoB64 }}" style="max-height:48px; max-width:140px; margin-bottom:4px;" />
            @endif
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-info">
                @if($companyAddress) {{ $companyAddress }}<br/> @endif
                @if($companyPhone) Tél : {{ $companyPhone }} @endif
                @if($companyEmail) &nbsp;| {{ $companyEmail }} @endif
                @if($companyNif)<br/>NIF : {{ $companyNif }} @endif
                @if($companyRccm) &nbsp;| RCCM : {{ $companyRccm }} @endif
            </div>
        </td>
        <td style="width:45%; text-align:right;">
            <div class="doc-title">Reçu de décaissement</div>
            <div class="doc-subtitle">
                Réf. commande : <strong>{{ $order->order_number ?? ('#'.str_pad($order->id,5,'0',STR_PAD_LEFT)) }}</strong>
            </div>
            <div class="doc-subtitle" style="margin-top:2px;">
                Date d'édition : {{ \Carbon\Carbon::now()->format('d/m/Y') }}
            </div>
            <div style="margin-top:5px;">
                <span class="status-badge {{ $isPaid ? 'badge-paid' : 'badge-partial' }}">
                    {{ $isPaid ? 'Entièrement décaissé' : 'Partiellement décaissé' }}
                </span>
            </div>
        </td>
    </tr>
</table>

<hr class="divider" />

{{-- ══ INFOS COMMANDE + FOURNISSEUR ══ --}}
<table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
    <tr>
        <td style="width:50%; vertical-align:top; padding-right:10px;">
            <div class="section-title">Commande</div>
            <table class="info-grid">
                <tr><td class="lbl">Intitulé</td><td class="val">{{ $order->title }}</td></tr>
                <tr><td class="lbl">Boutique</td><td class="val">{{ $order->boutique?->name ?? '—' }}</td></tr>
                <tr><td class="lbl">Demandeur</td><td class="val">{{ $order->user?->name ?? '—' }}</td></tr>
                <tr><td class="lbl">Montant commande</td><td class="val">{{ number_format($order->amount, 0, ',', ' ') }} FCFA</td></tr>
            </table>
        </td>
        <td style="width:50%; vertical-align:top; padding-left:10px; border-left:1px solid #ddd;">
            <div class="section-title">Fournisseur</div>
            <table class="info-grid">
                <tr><td class="lbl">Nom</td><td class="val">{{ $order->fournisseur?->name ?? '—' }}</td></tr>
                <tr><td class="lbl">Téléphone</td><td class="val">{{ $order->fournisseur?->phone ?? '—' }}</td></tr>
                <tr><td class="lbl">Email</td><td class="val">{{ $order->fournisseur?->email ?? '—' }}</td></tr>
                <tr><td class="lbl">Adresse</td><td class="val">{{ $order->fournisseur?->address ?? '—' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ══ TABLEAU DES DÉCAISSEMENTS ══ --}}
<div class="section-title">Détail des décaissements</div>
<table class="dec-table">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:16%">Date</th>
            <th style="width:18%">Mode de règlement</th>
            <th style="width:18%">Référence</th>
            <th style="width:25%">Notes</th>
            <th style="width:18%">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->decaissements as $i => $d)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td class="center">{{ \Carbon\Carbon::parse($d->decaissement_date)->format('d/m/Y') }}</td>
            <td class="center">{{ $d->modeReglement?->name ?? '—' }}</td>
            <td class="center">{{ $d->reference ?: '—' }}</td>
            <td>{{ $d->notes ?: '' }}</td>
            <td class="right"><strong>{{ number_format($d->montant, 0, ',', ' ') }}</strong></td>
        </tr>
        @endforeach
        {{-- Ligne total --}}
        <tr class="total-row">
            <td colspan="5" style="text-align:right; padding-right:8px;">Total décaissé</td>
            <td class="right">{{ number_format($totalDecaisse, 0, ',', ' ') }}</td>
        </tr>
        @if(!$isPaid)
        <tr>
            <td colspan="5" style="text-align:right; padding-right:8px; color:#92400e;">Reste à décaisser</td>
            <td class="right" style="color:#92400e; font-weight:bold;">{{ number_format($resteADecaisser, 0, ',', ' ') }}</td>
        </tr>
        @endif
    </tbody>
</table>

{{-- ══ MONTANT EN LETTRES ══ --}}
<div class="total-words-box">
    <div class="total-words-label">Arrêter le présent reçu à la somme de :</div>
    <strong>{{ $totalWords }}</strong>
</div>

{{-- ══ SIGNATURES ══ --}}
<table class="sig-table">
    <tr>
        <td>
            <span>Le Caissier</span>
            <div class="sig-box"></div>
        </td>
        <td>
            <span>Le Fournisseur</span>
            <div class="sig-box"></div>
        </td>
        <td>
            <span>Le Responsable</span>
            <div class="sig-box"></div>
        </td>
    </tr>
</table>

<div class="footer">
    Document généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }} — {{ $companyName }}
    @if($companyNif) | NIF : {{ $companyNif }} @endif
    @if($companyRccm) | RCCM : {{ $companyRccm }} @endif
</div>

</div>
</body>
</html>
