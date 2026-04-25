<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:9.5px; color:#1e293b; background:#fff; }
.page { padding:24px 36px; }

/* ── Clearfix ── */
.cf:after { content:""; display:table; clear:both; }

/* ── En-tête : float ── */
.header { margin-bottom:12px; }
.header-left  { float:left; width:55%; }
.header-right { float:right; width:42%; text-align:right; }
.logo-name    { font-size:16px; font-weight:700; color:#1d4ed8; }
.logo-sub     { font-size:8px; color:#3b82f6; font-weight:600; margin-top:1px; }
.logo-contact { font-size:7.5px; color:#64748b; margin-top:5px; line-height:1.6; }
.f-label  { font-size:7.5px; text-transform:uppercase; letter-spacing:1.2px; color:#94a3b8; font-weight:600; }
.f-num    { font-size:17px; font-weight:700; color:#1e293b; margin-top:1px; }
.f-date   { font-size:7.5px; color:#64748b; margin-top:3px; line-height:1.6; }
.f-badge  {
    display:inline-block; margin-top:4px;
    background:#fef9c3; border:1px solid #fde68a;
    color:#854d0e; border-radius:20px;
    padding:1px 7px; font-size:7.5px; font-weight:700; letter-spacing:0.5px;
}

/* ── Bande bleue : table ── */
.band {
    background:#1e3a8a;
    border-radius:5px; margin-bottom:11px;
    width:100%;
}
.band td { padding:9px 14px; }
.band-title  { font-size:11px; font-weight:700; color:#fff; }
.band-sub    { font-size:7.5px; color:rgba(255,255,255,0.75); margin-top:2px; }
.band-amt-lbl{ font-size:7.5px; color:rgba(255,255,255,0.75); text-align:right; }
.band-amount { font-size:19px; font-weight:700; color:#fff; text-align:right; }

/* ── Parties : table ── */
.parties { width:100%; margin-bottom:11px; border-collapse:separate; border-spacing:10px 0; }
.parties td { width:50%; border:1px solid #e2e8f0; border-radius:5px; padding:8px 11px; vertical-align:top; }
.p-label { font-size:7px; text-transform:uppercase; letter-spacing:1px; color:#3b82f6; font-weight:700; margin-bottom:4px; }
.p-name  { font-size:10px; font-weight:700; color:#1e293b; margin-bottom:2px; }
.p-info  { font-size:7.5px; color:#64748b; line-height:1.6; }

/* ── Tableau prestations ── */
table.prest { width:100%; border-collapse:collapse; margin-bottom:11px; }
table.prest thead th {
    background:#1d4ed8; color:#fff;
    font-size:7.5px; font-weight:600;
    text-transform:uppercase; letter-spacing:0.5px;
    padding:6px 9px; text-align:left;
}
table.prest thead th:last-child { text-align:right; }
table.prest tbody td {
    padding:5.5px 9px; font-size:8.5px;
    border-bottom:1px solid #f1f5f9; vertical-align:top;
}
table.prest tbody tr:last-child td { border-bottom:none; }
table.prest tbody tr:nth-child(even) td { background:#f8fafc; }
.td-r { text-align:right; font-weight:700; white-space:nowrap; }
.pt   { font-weight:700; color:#1e293b; margin-bottom:1px; font-size:8.5px; }
.pd   { font-size:7.5px; color:#64748b; line-height:1.4; }
.tag  {
    display:inline-block; background:#dbeafe; color:#1d4ed8;
    border-radius:8px; padding:0px 5px; font-size:7px; font-weight:600;
    margin-top:2px; margin-right:2px;
}

/* ── Bottom : conditions (left) + totaux (right) ── */
.bottom { width:100%; margin-bottom:11px; border-collapse:separate; border-spacing:10px 0; }
.bottom td { vertical-align:top; }
.bottom td.conds { width:58%; }
.bottom td.tots  { width:42%; }

.cond { border-radius:5px; padding:7px 9px; margin-bottom:5px; }
.cond:last-child { margin-bottom:0; }
.cond-b { background:#eff6ff; border:1px solid #bfdbfe; }
.cond-g { background:#f0fdf4; border:1px solid #bbf7d0; }
.cond-a { background:#fffbeb; border:1px solid #fde68a; }
.ct { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:2px; }
.cond-b .ct { color:#1d4ed8; }
.cond-g .ct { color:#15803d; }
.cond-a .ct { color:#b45309; }
.cb { font-size:7.5px; color:#475569; line-height:1.5; }

.trow { width:100%; border-collapse:collapse; }
.trow td { padding:4px 9px; font-size:8.5px; }
.trow .sep td { border-top:1px solid #e2e8f0; }
.trow .ttc td {
    background:#1d4ed8; color:#fff;
    font-weight:700; font-size:11px;
    border-radius:4px; padding:7px 11px;
}
.al { text-align:left; }
.ar { text-align:right; }

/* ── RIB + Signature : float ── */
.rib-sig { margin-bottom:10px; }
.rib-box {
    float:left; width:56%;
    border:1px solid #e2e8f0; border-radius:5px;
    padding:7px 10px;
}
.sig-box-wrap { float:right; width:38%; text-align:center; }
.rib-title { font-size:7.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin-bottom:5px; }
.rib-grid { width:100%; border-collapse:collapse; }
.rib-grid td { padding:2px 6px 2px 0; vertical-align:top; width:50%; }
.rl { font-size:7px; color:#94a3b8; text-transform:uppercase; }
.rv { font-size:8.5px; font-weight:600; margin-top:1px; }
.sig-rect {
    border:1px dashed #cbd5e1; border-radius:5px;
    height:52px; width:100%;
    font-size:7px; color:#cbd5e1;
    text-align:center; padding-top:20px;
    margin-bottom:3px;
}
.sig-name  { font-size:8.5px; font-weight:700; color:#1e293b; }
.sig-label { font-size:7px; color:#64748b; }

/* ── Footer : float ── */
.footer {
    border-top:1px solid #e2e8f0; padding-top:7px; margin-top:2px;
}
.footer-l { float:left;  font-size:7px; color:#94a3b8; }
.footer-r { float:right; font-size:7px; color:#94a3b8; }
</style>
</head>
<body>
<div class="page">

    {{-- ── EN-TÊTE ── --}}
    <div class="header cf">
        <div class="header-left">
            <div class="logo-name">Enock MAMBOU</div>
            <div class="logo-sub">Consultant Software Engineer &nbsp;·&nbsp; Expert Transformation Digitale</div>
            <div class="logo-contact">
                enockmambou@gmail.com &nbsp;·&nbsp; +221 77 136 34 &nbsp;·&nbsp; Dakar, Sénégal
            </div>
        </div>
        <div class="header-right">
            <div class="f-label">Proposition de facture</div>
            <div class="f-num">PROP-2026-001</div>
            <div class="f-date">
                Émise le : {{ \Carbon\Carbon::now()->locale('fr')->translatedFormat('d F Y') }}<br>
                Valable jusqu'au : {{ \Carbon\Carbon::now()->addDays(60)->locale('fr')->translatedFormat('d F Y') }}
            </div>
            <div class="f-badge">En attente de validation</div>
        </div>
    </div>

    {{-- ── BANDE BLEUE ── --}}
    <table class="band" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="band-title">Développement Application AchatPro</div>
                <div class="band-sub">Système de gestion des achats &amp; autorisations de paiement — Fortune Capital Group</div>
            </td>
            <td style="text-align:right; white-space:nowrap;">
                <div class="band-amt-lbl">Montant total</div>
                <div class="band-amount">500 000 XOF</div>
            </td>
        </tr>
    </table>

    {{-- ── PARTIES ── --}}
    <table class="parties" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="p-label">Prestataire</div>
                <div class="p-name">Enock MAMBOU</div>
                <div class="p-info">
                    Consultant Software Engineer<br>
                    Expert Transformation Digitale<br>
                    +221 77 136 34 · enockmambou@gmail.com · Dakar, Sénégal
                </div>
            </td>
            <td>
                <div class="p-label">Client</div>
                <div class="p-name">Fortune Capital Group</div>
                <div class="p-info">
                    À l'attention de M. DIOUF<br>
                    Dakar, Sénégal<br>
                    Fimoluse · ATRA · Elycargo · Senegui
                </div>
            </td>
        </tr>
    </table>

    {{-- ── PRESTATIONS ── --}}
    <table class="prest" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:56%">Désignation</th>
                <th style="width:18%">Unité</th>
                <th style="width:22%">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="color:#94a3b8;">01</td>
                <td>
                    <div class="pt">Conception &amp; développement de l'application web</div>
                    <div class="pd">Back-end Laravel 11, front-end Vue 3 + TypeScript, circuit EB→DAP, 4 niveaux de validation, seuils configurables.</div>
                    <span class="tag">Circuit complet</span><span class="tag">Multi-sociétés</span><span class="tag">Seuils configurables</span>
                </td>
                <td style="color:#64748b;">Forfait</td>
                <td class="td-r">280 000 XOF</td>
            </tr>
            <tr>
                <td style="color:#94a3b8;">02</td>
                <td>
                    <div class="pt">Tableaux de bord &amp; reporting</div>
                    <div class="pd">Dashboard DF consolidé groupe (budgets, alertes, dépenses mensuelles), dashboard admin filtrable, export Excel.</div>
                    <span class="tag">Vue groupe</span><span class="tag">Alertes budgétaires</span><span class="tag">Export Excel</span>
                </td>
                <td style="color:#64748b;">Forfait</td>
                <td class="td-r">80 000 XOF</td>
            </tr>
            <tr>
                <td style="color:#94a3b8;">03</td>
                <td>
                    <div class="pt">Exports PDF &amp; documentation</div>
                    <div class="pd">PDF par DAP (circuit signé), guide utilisateur DF (4 pages), proposition de facture.</div>
                    <span class="tag">PDF DAP</span><span class="tag">Guide DF</span>
                </td>
                <td style="color:#64748b;">Forfait</td>
                <td class="td-r">40 000 XOF</td>
            </tr>
            <tr>
                <td style="color:#94a3b8;">04</td>
                <td>
                    <div class="pt">Données de démonstration &amp; déploiement</div>
                    <div class="pd">70+ transactions jan. 2025–mars 2026, déploiement serveur, configuration domaine.</div>
                    <span class="tag">Seed réaliste</span><span class="tag">Mise en ligne</span>
                </td>
                <td style="color:#64748b;">Forfait</td>
                <td class="td-r">60 000 XOF</td>
            </tr>
            <tr>
                <td style="color:#94a3b8;">05</td>
                <td>
                    <div class="pt">Formation &amp; support post-livraison</div>
                    <div class="pd">Accompagnement prise en main, documentation et support technique 60 jours.</div>
                    <span class="tag">Guide PDF</span><span class="tag">Support 60j</span>
                </td>
                <td style="color:#64748b;">Forfait</td>
                <td class="td-r">40 000 XOF</td>
            </tr>
        </tbody>
    </table>

    {{-- ── CONDITIONS + TOTAUX ── --}}
    <table class="bottom" cellpadding="0" cellspacing="0">
        <tr>
            <td class="conds">
                <div class="cond cond-b">
                    <div class="ct">Modalités de paiement</div>
                    <div class="cb">50 % à la commande : <strong>250 000 XOF</strong><br>50 % à la livraison : <strong>250 000 XOF</strong></div>
                </div>
                <div class="cond cond-g">
                    <div class="ct">Livraison</div>
                    <div class="cb">Application en version démo sur notre hébergeur. La livraison sur l'infrastructure cliente interviendra après accord et règlement de l'acompte. Support <strong>60 jours</strong> inclus à compter de la livraison définitive.</div>
                </div>
                <div class="cond cond-a">
                    <div class="ct">Validité</div>
                    <div class="cb">Offre valable <strong>60 jours</strong> à compter de la date d'émission.</div>
                </div>
            </td>
            <td class="tots">
                <table class="trow" cellpadding="0" cellspacing="0">
                    <tr class="sep"><td class="al">Sous-total HT</td><td class="ar">500 000 XOF</td></tr>
                    <tr><td class="al" style="color:#64748b;">TVA</td><td class="ar" style="color:#64748b;">Exonéré</td></tr>
                    <tr class="ttc"><td class="al">TOTAL À PAYER</td><td class="ar">500 000 XOF</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── RIB + SIGNATURE ── --}}
    <div class="rib-sig cf">
        <div class="rib-box">
            <div class="rib-title">Coordonnées bancaires</div>
            <table class="rib-grid" cellpadding="0" cellspacing="0">
                <tr>
                    <td><div class="rl">Banque</div><div class="rv">Djamo</div></td>
                    <td><div class="rl">Titulaire</div><div class="rv">Enock MAMBOU</div></td>
                </tr>
                <tr>
                    <td><div class="rl">N° Compte</div><div class="rv">SN191 01001 010948002747 04</div></td>
                    <td><div class="rl">Mobile Money</div><div class="rv">Wave / Orange Money</div></td>
                </tr>
            </table>
        </div>
        <div class="sig-box-wrap">
            <div class="sig-rect">Signature &amp; cachet client</div>
            <div class="sig-name">M. DIOUF</div>
            <div class="sig-label">Bon pour accord</div>
        </div>
    </div>

    {{-- ── FOOTER ── --}}
    <div class="footer cf">
        <div class="footer-l">Enock MAMBOU · Consultant Software Engineer · Expert Transformation Digitale · Dakar, Sénégal</div>
        <div class="footer-r">PROP-2026-001 · {{ \Carbon\Carbon::now()->locale('fr')->translatedFormat('d F Y') }}</div>
    </div>

</div>
</body>
</html>
