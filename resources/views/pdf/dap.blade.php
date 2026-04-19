<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

        .page { padding: 32px 40px; }

        /* Header */
        .header { border-bottom: 3px solid #1d4ed8; padding-bottom: 16px; margin-bottom: 24px; }
        .header-inner { display: flex; justify-content: space-between; align-items: flex-start; }
        .groupe-nom { font-size: 20px; font-weight: 700; color: #1d4ed8; letter-spacing: -0.3px; }
        .groupe-sub { font-size: 10px; color: #64748b; margin-top: 2px; }
        .dap-ref-box { text-align: right; }
        .dap-ref { font-size: 15px; font-weight: 700; color: #1e293b; font-family: monospace; }
        .dap-date { font-size: 10px; color: #64748b; margin-top: 3px; }

        /* Title */
        .doc-title { text-align: center; margin: 0 0 22px; }
        .doc-title h1 { font-size: 14px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #1d4ed8; }
        .doc-title .statut { display: inline-block; margin-top: 5px; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .statut-payee    { background: #dbeafe; color: #1d4ed8; }
        .statut-validee  { background: #d1fae5; color: #065f46; }
        .statut-en_cours { background: #fef9c3; color: #854d0e; }
        .statut-rejetee  { background: #fee2e2; color: #991b1b; }

        /* Sections */
        .section { margin-bottom: 18px; }
        .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #3b82f6; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px; }

        .grid2 { display: flex; gap: 0; }
        .col { flex: 1; padding-right: 20px; }
        .col:last-child { padding-right: 0; }

        .field { margin-bottom: 9px; }
        .field-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .field-value { font-size: 11px; color: #1e293b; font-weight: 500; }

        /* Montant box */
        .montant-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; display: flex; align-items: center; gap: 16px; }
        .montant-label { font-size: 10px; color: #3b82f6; font-weight: 600; text-transform: uppercase; }
        .montant-value { font-size: 22px; font-weight: 700; color: #1d4ed8; }

        /* Validations */
        .validation-row { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 5px; margin-bottom: 6px; }
        .val-approuve { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .val-rejete   { background: #fff1f2; border: 1px solid #fecdd3; }
        .val-icon { width: 14px; height: 14px; font-size: 11px; font-weight: 700; text-align: center; border-radius: 50%; line-height: 14px; }
        .icon-ok  { background: #22c55e; color: #fff; }
        .icon-ko  { background: #ef4444; color: #fff; }
        .val-niveau { font-weight: 600; font-size: 11px; flex: 1; }
        .val-name { font-size: 10px; color: #64748b; }
        .val-date { font-size: 10px; color: #94a3b8; }
        .val-comment { font-size: 10px; color: #64748b; font-style: italic; margin-top: 2px; }

        /* Paiement box */
        .paiement-box { background: #eff6ff; border: 1px solid #93c5fd; border-radius: 6px; padding: 12px; }
        .paiement-title { font-weight: 700; color: #1d4ed8; margin-bottom: 8px; font-size: 11px; }
        .paiement-grid { display: flex; gap: 16px; }
        .paiement-item .pl { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .paiement-item .pv { font-weight: 600; }

        /* Footer */
        .footer { border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 28px; display: flex; justify-content: space-between; align-items: center; }
        .footer-text { font-size: 9px; color: #94a3b8; }
        .footer-confidential { font-size: 9px; color: #cbd5e1; font-style: italic; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-inner">
            <div>
                <div class="groupe-nom">{{ $dap->expressionBesoin->entreprise->groupe->nom ?? 'Fortune Capital' }}</div>
                <div class="groupe-sub">{{ $dap->expressionBesoin->entreprise->nom }} · {{ $dap->expressionBesoin->entreprise->code }}</div>
            </div>
            <div class="dap-ref-box">
                <div class="dap-ref">{{ $dap->reference }}</div>
                <div class="dap-date">Créée le {{ \Carbon\Carbon::parse($dap->created_at)->locale('fr')->translatedFormat('d F Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Titre --}}
    <div class="doc-title">
        <h1>Demande d'Autorisation de Paiement</h1>
        @php
            $statutLabels = ['en_cours' => 'En cours de validation', 'validee' => 'Validée', 'rejetee' => 'Rejetée', 'payee' => 'Payée'];
        @endphp
        <span class="statut statut-{{ $dap->statut }}">{{ $statutLabels[$dap->statut] ?? $dap->statut }}</span>
    </div>

    {{-- Montant --}}
    <div class="montant-box">
        <div>
            <div class="montant-label">Montant demandé</div>
            <div class="montant-value">{{ number_format($dap->expressionBesoin->montant, 0, ',', ' ') }} FCFA</div>
        </div>
        @if($dap->budgetAnnuel)
        <div style="border-left: 1px solid #bfdbfe; padding-left: 16px;">
            <div class="montant-label">Budget rattaché</div>
            <div style="font-size: 11px; font-weight: 600; color: #1e293b; margin-top: 2px;">{{ $dap->budgetAnnuel->libelle }}</div>
        </div>
        @endif
    </div>

    {{-- Demandeur & Demande --}}
    <div class="section">
        <div class="section-title">Informations de la demande</div>
        <div class="grid2">
            <div class="col">
                <div class="field">
                    <div class="field-label">Demandeur</div>
                    <div class="field-value">{{ $dap->expressionBesoin->user->name }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Fonction</div>
                    <div class="field-value">{{ $dap->expressionBesoin->user->fonction ?? '—' }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Société</div>
                    <div class="field-value">{{ $dap->expressionBesoin->entreprise->nom }}</div>
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <div class="field-label">Référence EB</div>
                    <div class="field-value" style="font-family: monospace;">{{ $dap->expressionBesoin->reference }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Bénéficiaire</div>
                    <div class="field-value">{{ $dap->expressionBesoin->beneficiaire }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Date de la demande</div>
                    <div class="field-value">{{ \Carbon\Carbon::parse($dap->expressionBesoin->created_at)->locale('fr')->translatedFormat('d F Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Objet & Description --}}
    <div class="section">
        <div class="section-title">Objet & justification</div>
        <div class="field">
            <div class="field-label">Objet</div>
            <div class="field-value" style="font-size: 12px;">{{ $dap->expressionBesoin->objet }}</div>
        </div>
        <div class="field" style="margin-top: 6px;">
            <div class="field-label">Description</div>
            <div class="field-value" style="line-height: 1.5; font-weight: 400;">{{ $dap->expressionBesoin->description }}</div>
        </div>
        @if($dap->expressionBesoin->justification)
        <div class="field" style="margin-top: 6px;">
            <div class="field-label">Justification</div>
            <div class="field-value" style="line-height: 1.5; font-weight: 400; color: #475569;">{{ $dap->expressionBesoin->justification }}</div>
        </div>
        @endif
    </div>

    {{-- Circuit de validation --}}
    <div class="section">
        <div class="section-title">Circuit de validation</div>
        @forelse($dap->validations->sortBy('niveau_validation.ordre') as $v)
        <div class="validation-row {{ $v->statut === 'approuve' ? 'val-approuve' : 'val-rejete' }}">
            <div class="val-icon {{ $v->statut === 'approuve' ? 'icon-ok' : 'icon-ko' }}">
                {{ $v->statut === 'approuve' ? '✓' : '✗' }}
            </div>
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="val-niveau">{{ $v->niveauValidation->nom }}</span>
                    <span class="val-date">{{ \Carbon\Carbon::parse($v->validated_at)->locale('fr')->translatedFormat('d F Y') }}</span>
                </div>
                <div class="val-name">{{ $v->validateur->name }} · {{ $v->validateur->fonction ?? '' }}</div>
                @if($v->commentaire)
                <div class="val-comment">« {{ $v->commentaire }} »</div>
                @endif
            </div>
        </div>
        @empty
        <p style="font-size: 10px; color: #94a3b8;">Aucune validation enregistrée</p>
        @endforelse
    </div>

    {{-- Paiement --}}
    @if($dap->paiement)
    <div class="section">
        <div class="section-title">Paiement</div>
        <div class="paiement-box">
            <div class="paiement-title">Paiement effectué</div>
            <div class="paiement-grid">
                <div class="paiement-item">
                    <div class="pl">Montant</div>
                    <div class="pv">{{ number_format($dap->paiement->montant, 0, ',', ' ') }} FCFA</div>
                </div>
                <div class="paiement-item">
                    <div class="pl">Date</div>
                    <div class="pv">{{ \Carbon\Carbon::parse($dap->paiement->date_paiement)->locale('fr')->translatedFormat('d F Y') }}</div>
                </div>
                <div class="paiement-item">
                    <div class="pl">Mode</div>
                    <div class="pv" style="text-transform: capitalize;">{{ str_replace('_', ' ', $dap->paiement->mode_paiement) }}</div>
                </div>
                @if($dap->paiement->reference)
                <div class="paiement-item">
                    <div class="pl">Référence</div>
                    <div class="pv">{{ $dap->paiement->reference }}</div>
                </div>
                @endif
                @if($dap->paiement->banque)
                <div class="paiement-item">
                    <div class="pl">Banque</div>
                    <div class="pv">{{ $dap->paiement->banque }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-text">Document généré le {{ now()->locale('fr')->translatedFormat('d F Y à H:i') }}</div>
        <div class="footer-confidential">Document confidentiel — Fortune Capital Group</div>
    </div>

</div>
</body>
</html>
