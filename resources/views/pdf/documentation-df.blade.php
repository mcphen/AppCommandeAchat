<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

/* ── Couverture ── */
.cover {
    page-break-after: always;
    min-height: 100vh;
    background: linear-gradient(160deg, #1e3a8a 0%, #1d4ed8 55%, #3b82f6 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 60px 50px 40px;
    color: #fff;
}
.cover-top {}
.cover-badge {
    display: inline-block;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 20px;
    padding: 5px 14px;
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 32px;
}
.cover-title {
    font-size: 34px;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
}
.cover-subtitle {
    font-size: 16px;
    opacity: 0.8;
    margin-bottom: 8px;
}
.cover-role {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 20px;
}
.cover-divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.25);
    margin: 40px 0 20px;
}
.cover-bottom {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.cover-group { font-size: 13px; font-weight: 700; opacity: 0.9; }
.cover-date  { font-size: 10px; opacity: 0.6; }
.cover-decoration {
    position: absolute;
    right: 40px;
    top: 80px;
    width: 180px;
    height: 180px;
    border-radius: 50%;
    border: 30px solid rgba(255,255,255,0.07);
}
.cover-decoration2 {
    position: absolute;
    right: 10px;
    top: 200px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 20px solid rgba(255,255,255,0.05);
}

/* ── Mise en page ── */
.page { padding: 36px 44px; page-break-after: always; position: relative; }
.page:last-child { page-break-after: avoid; }

/* En-tête de page */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #1d4ed8;
    padding-bottom: 10px;
    margin-bottom: 24px;
}
.page-header-title { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #3b82f6; font-weight: 700; }
.page-header-logo  { font-size: 12px; font-weight: 700; color: #1d4ed8; }

/* Section */
.section { margin-bottom: 26px; }
.section-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e3a8a;
    border-left: 3px solid #3b82f6;
    padding-left: 10px;
    margin-bottom: 12px;
}
.section-num {
    display: inline-block;
    background: #1d4ed8;
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    text-align: center;
    line-height: 18px;
    font-size: 10px;
    font-weight: 700;
    margin-right: 8px;
    vertical-align: middle;
}

/* Texte */
p  { margin-bottom: 8px; line-height: 1.6; }
li { margin-bottom: 5px; line-height: 1.6; margin-left: 16px; }
ul { margin-bottom: 10px; }
strong { font-weight: 700; }
em { font-style: italic; color: #475569; }

/* Infobulles */
.info-box {
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 14px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.info-blue  { background: #eff6ff; border: 1px solid #bfdbfe; }
.info-green { background: #f0fdf4; border: 1px solid #bbf7d0; }
.info-amber { background: #fffbeb; border: 1px solid #fde68a; }
.info-red   { background: #fff1f2; border: 1px solid #fecdd3; }
.info-icon  { font-size: 14px; font-weight: 700; width: 20px; text-align: center; margin-top: 1px; }
.info-blue  .info-icon { color: #1d4ed8; }
.info-green .info-icon { color: #15803d; }
.info-amber .info-icon { color: #b45309; }
.info-red   .info-icon { color: #b91c1c; }
.info-content { flex: 1; }
.info-content strong { font-size: 11px; }
.info-content p { font-size: 10px; margin-bottom: 0; }

/* Tableau */
table.doc-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
table.doc-table th {
    background: #1d4ed8;
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 10px;
    text-align: left;
}
table.doc-table td {
    padding: 8px 10px;
    font-size: 10px;
    border-bottom: 1px solid #e2e8f0;
    line-height: 1.5;
}
table.doc-table tr:nth-child(even) td { background: #f8fafc; }

/* Circuit visuel */
.circuit {
    display: flex;
    align-items: center;
    gap: 0;
    margin: 14px 0;
    overflow: hidden;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.circuit-step {
    flex: 1;
    text-align: center;
    padding: 12px 6px;
    font-size: 9px;
    border-right: 1px solid #e2e8f0;
}
.circuit-step:last-child { border-right: none; }
.circuit-step .cs-icon { font-size: 16px; margin-bottom: 4px; }
.circuit-step .cs-label { font-weight: 700; font-size: 9px; color: #1e293b; }
.circuit-step .cs-sub   { font-size: 8px; color: #64748b; margin-top: 2px; }
.circuit-step.active    { background: #eff6ff; }
.circuit-step.active .cs-label { color: #1d4ed8; }
.circuit-arrow          { font-size: 16px; color: #94a3b8; padding: 0 2px; }

/* Étapes numérotées */
.steps { display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px; }
.step  { display: flex; gap: 12px; align-items: flex-start; }
.step-num {
    width: 24px; height: 24px; min-width: 24px;
    border-radius: 50%;
    background: #1d4ed8;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    text-align: center;
    line-height: 24px;
}
.step-body { flex: 1; padding-top: 2px; }
.step-title { font-weight: 700; font-size: 11px; margin-bottom: 3px; }
.step-desc  { font-size: 10px; color: #475569; line-height: 1.5; }

/* Highlight role DF */
.df-highlight {
    background: #1d4ed8;
    color: #fff;
    border-radius: 4px;
    padding: 1px 6px;
    font-size: 10px;
    font-weight: 700;
}
.tag {
    display: inline-block;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 9px;
    font-weight: 600;
}
.tag-blue   { background: #dbeafe; color: #1d4ed8; }
.tag-green  { background: #d1fae5; color: #065f46; }
.tag-amber  { background: #fef9c3; color: #854d0e; }
.tag-red    { background: #fee2e2; color: #991b1b; }

/* Footer */
.page-footer {
    position: fixed;
    bottom: 24px;
    left: 44px;
    right: 44px;
    display: flex;
    justify-content: space-between;
    font-size: 8px;
    color: #94a3b8;
    border-top: 1px solid #e2e8f0;
    padding-top: 6px;
}
</style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- PAGE DE COUVERTURE                                     --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="cover">
    <div class="cover-decoration"></div>
    <div class="cover-decoration2"></div>

    <div class="cover-top">
        <div class="cover-badge">Guide Utilisateur — Confidentiel</div>
        <div class="cover-title">Système de Gestion<br>des Autorisations<br>de Paiement</div>
        <div class="cover-subtitle">Application DAP — Fortune Capital Group</div>
        <div class="cover-role">Direction Financière (DF)</div>
    </div>

    <div>
        <hr class="cover-divider" />
        <div class="cover-bottom">
            <div>
                <div class="cover-group">Fortune Capital Group</div>
                <div class="cover-date" style="margin-top:4px;">Fimoluse · ATRA · Elycargo · Senegui</div>
            </div>
            <div class="cover-date" style="text-align:right;">
                Version 1.0<br>
                Avril 2026
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- PAGE 1 : Présentation & Circuit                        --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="page">
    <div class="page-header">
        <span class="page-header-title">Présentation du système</span>
        <span class="page-header-logo">Fortune Capital · DAP</span>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">1</span>Objectif de l'application</div>
        <p>
            L'application <strong>DAP (Demande d'Autorisation de Paiement)</strong> centralise l'ensemble des demandes
            d'achats et de paiements des quatre sociétés du groupe Fortune Capital. Elle garantit une
            <strong>traçabilité complète</strong>, un <strong>contrôle budgétaire en temps réel</strong> et une
            <strong>chaîne de validation hiérarchique</strong> adaptée aux montants engagés.
        </p>
        <p>
            Chaque dépense, quelle que soit la société (Fimoluse, ATRA, Elycargo ou Senegui), passe par un circuit
            formalisé avant tout décaissement. <strong>Aucun paiement ne peut être effectué sans que toutes les
            validations requises aient été accordées.</strong>
        </p>

        <div class="info-box info-blue">
            <div class="info-icon">i</div>
            <div class="info-content">
                <strong>Accès à l'application</strong>
                <p>L'application est accessible via le navigateur web de votre poste. Connectez-vous avec l'adresse
                e-mail et le mot de passe fournis par l'administrateur. La session reste active 8 heures.</p>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">2</span>Circuit complet d'une demande</div>
        <p style="font-size:10px; color:#64748b; margin-bottom:10px;">De la demande initiale jusqu'au paiement effectif.</p>

        <div class="circuit">
            <div class="circuit-step">
                <div class="cs-icon">✏️</div>
                <div class="cs-label">Expression<br>de Besoin</div>
                <div class="cs-sub">Employé</div>
            </div>
            <div class="circuit-step">
                <div class="cs-icon">📋</div>
                <div class="cs-label">Validation<br>Comptable</div>
                <div class="cs-sub">Compta</div>
            </div>
            <div class="circuit-step active">
                <div class="cs-icon">📄</div>
                <div class="cs-label">Création<br>DAP</div>
                <div class="cs-sub">Compta → système</div>
            </div>
            <div class="circuit-step active" style="background:#dbeafe;">
                <div class="cs-icon">✅</div>
                <div class="cs-label" style="color:#1d4ed8;">Validation DF</div>
                <div class="cs-sub" style="color:#3b82f6;">Vous</div>
            </div>
            <div class="circuit-step">
                <div class="cs-icon">✅</div>
                <div class="cs-label">Validation<br>DG / PDG</div>
                <div class="cs-sub">Si ≥ 500 000 F</div>
            </div>
            <div class="circuit-step">
                <div class="cs-icon">💳</div>
                <div class="cs-label">Paiement</div>
                <div class="cs-sub">Administration</div>
            </div>
        </div>

        <div class="info-box info-amber">
            <div class="info-icon">!</div>
            <div class="info-content">
                <strong>Votre position dans le circuit</strong>
                <p>En tant que Directeur Financier, vous validez <strong>toutes les DAP</strong> sans exception,
                quel que soit le montant. Votre signature est obligatoire avant toute transmission au DG ou au PDG.</p>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">3</span>Seuils de validation</div>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Niveau</th>
                    <th>Responsable</th>
                    <th>Montant plancher</th>
                    <th>Applicable à</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="tag tag-blue">Comptabilité</span></td>
                    <td>Responsable Comptable</td>
                    <td>Toutes les demandes</td>
                    <td>Valide l'EB et crée la DAP</td>
                </tr>
                <tr>
                    <td><span class="tag tag-amber">Direction Financière</span></td>
                    <td><strong>Vous (DF)</strong></td>
                    <td>Toutes les DAP</td>
                    <td>1ère signature obligatoire</td>
                </tr>
                <tr>
                    <td><span class="tag tag-amber">Direction Générale</span></td>
                    <td>Directeur Général</td>
                    <td>≥ 500 000 FCFA</td>
                    <td>Après validation DF</td>
                </tr>
                <tr>
                    <td><span class="tag tag-red">Présidence</span></td>
                    <td>PDG</td>
                    <td>≥ 2 000 000 FCFA</td>
                    <td>Après validation DG</td>
                </tr>
            </tbody>
        </table>

        <div class="info-box info-green">
            <div class="info-icon">✓</div>
            <div class="info-content">
                <strong>Qui effectue le paiement ?</strong>
                <p>Le paiement est enregistré dans le système par <strong>l'administration du groupe</strong> une fois que
                toutes les validations requises sont accordées. Le DF ne saisit pas le paiement — il autorise
                uniquement. L'administration joint le mode (virement, chèque…), la banque et la référence.</p>
            </div>
        </div>
    </div>

    <div class="page-footer">
        <span>Guide Utilisateur DAP — Direction Financière</span>
        <span>Fortune Capital Group — Confidentiel</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- PAGE 2 : Guide pratique DF                             --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="page">
    <div class="page-header">
        <span class="page-header-title">Guide pratique — Directeur Financier</span>
        <span class="page-header-logo">Fortune Capital · DAP</span>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">4</span>Votre tableau de bord</div>
        <p>Dès votre connexion, le tableau de bord affiche une <strong>vue consolidée du groupe</strong> :</p>

        <div class="steps">
            <div class="step">
                <div class="step-num">A</div>
                <div class="step-body">
                    <div class="step-title">Indicateurs clés (bandeau supérieur)</div>
                    <div class="step-desc">
                        — <strong>À signer</strong> : nombre de DAP qui attendent votre signature aujourd'hui.<br>
                        — <strong>Mes approbations</strong> : total cumulé des DAP que vous avez approuvées.<br>
                        — <strong>Payé {{ now()->year }}</strong> : montant total décaissé sur l'exercice en cours.<br>
                        — <strong>Engagé en cours</strong> : montant des DAP encore en validation (non payées).
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">B</div>
                <div class="step-body">
                    <div class="step-title">Budgets par société (4 cartes)</div>
                    <div class="step-desc">
                        Chaque société dispose d'une carte affichant : budget annuel alloué, montant consommé,
                        montant disponible et une <strong>barre de progression colorée</strong>.<br>
                        — <span style="color:#15803d; font-weight:600;">Vert</span> : consommation normale (moins de 60 %)<br>
                        — <span style="color:#b45309; font-weight:600;">Orange</span> : vigilance (60 à 79 %)<br>
                        — <span style="color:#b91c1c; font-weight:600;">Rouge + icône alerte</span> : seuil critique (80 % et plus)
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">C</div>
                <div class="step-body">
                    <div class="step-title">DAPs en attente de votre signature</div>
                    <div class="step-desc">
                        Tableau listant toutes les DAP qui nécessitent votre accord. Pour chaque ligne :
                        référence, objet, société émettrice, montant et un bouton direct <strong>« Valider »</strong>.
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">D</div>
                <div class="step-body">
                    <div class="step-title">Tableau des dépenses mensuelles (12 mois)</div>
                    <div class="step-desc">
                        Récapitulatif mensuel des décaissements effectifs du groupe, du mois le plus ancien
                        au mois en cours. Permet de détecter les pics de dépenses.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">5</span>Valider ou rejeter une DAP</div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-body">
                    <div class="step-title">Accéder à la DAP</div>
                    <div class="step-desc">
                        Depuis le tableau de bord → cliquez sur <strong>« Valider »</strong> en face de la DAP souhaitée.
                        Vous pouvez aussi passer par le menu latéral <em>Validations DAP</em>.
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-body">
                    <div class="step-title">Lire le dossier</div>
                    <div class="step-desc">
                        La fiche DAP affiche : l'objet, la société, le montant, le budget rattaché,
                        la description complète, la justification, les pièces jointes et les validations déjà accordées.
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-body">
                    <div class="step-title">Prendre votre décision</div>
                    <div class="step-desc">
                        Dans le bloc <strong>« Votre décision »</strong> en bas de page :<br>
                        — Ajoutez un commentaire (facultatif mais recommandé pour un rejet).<br>
                        — Cliquez <strong>« Approuver »</strong> pour valider et transmettre au niveau suivant.<br>
                        — Cliquez <strong>« Rejeter »</strong> pour refuser (un motif est alors demandé).
                    </div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-body">
                    <div class="step-title">Télécharger le PDF de la DAP</div>
                    <div class="step-desc">
                        Le bouton <strong>« PDF »</strong> en haut à droite de la fiche génère un document officiel
                        reprenant toutes les informations et le circuit de validation pour archivage physique.
                    </div>
                </div>
            </div>
        </div>

        <div class="info-box info-amber">
            <div class="info-icon">!</div>
            <div class="info-content">
                <strong>Que se passe-t-il après votre rejet ?</strong>
                <p>En cas de rejet par votre soin, la DAP est marquée « Rejetée », l'Expression de Besoin est renvoyée
                à l'employé demandeur avec votre motif. Aucun paiement ne peut être initié. L'employé
                pourra soumettre une nouvelle demande si nécessaire.</p>
            </div>
        </div>
    </div>

    <div class="page-footer">
        <span>Guide Utilisateur DAP — Direction Financière</span>
        <span>Fortune Capital Group — Confidentiel</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- PAGE 3 : Rôles, budgets & FAQ                          --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="page">
    <div class="page-header">
        <span class="page-header-title">Rôles, budgets & questions fréquentes</span>
        <span class="page-header-logo">Fortune Capital · DAP</span>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">6</span>Tableau des rôles et responsabilités</div>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Profil</th>
                    <th>Peut faire</th>
                    <th>Ne peut pas faire</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Employé (société)</strong></td>
                    <td>Créer une Expression de Besoin, joindre des pièces, consulter l'avancement</td>
                    <td>Créer une DAP, valider, payer</td>
                </tr>
                <tr>
                    <td><strong>Comptabilité</strong></td>
                    <td>Valider ou rejeter les EB, créer les DAP, rattacher un budget</td>
                    <td>Approuver les DAP (DF, DG, PDG), payer</td>
                </tr>
                <tr>
                    <td><strong>Directeur Financier</strong><br><em>(vous)</em></td>
                    <td>Approuver / rejeter toutes les DAP, consulter les budgets groupe, télécharger les PDF</td>
                    <td>Créer une EB, créer une DAP, saisir un paiement</td>
                </tr>
                <tr>
                    <td><strong>Directeur Général</strong></td>
                    <td>Approuver / rejeter les DAP ≥ 500 000 FCFA</td>
                    <td>Valider les DAP &lt; 500 000 FCFA, payer</td>
                </tr>
                <tr>
                    <td><strong>PDG</strong></td>
                    <td>Approuver / rejeter les DAP ≥ 2 000 000 FCFA</td>
                    <td>Valider les DAP &lt; 2 000 000 FCFA, payer</td>
                </tr>
                <tr>
                    <td><strong>Administration</strong></td>
                    <td>Gérer tous les paramètres, enregistrer les paiements, accéder à toutes les données</td>
                    <td>—</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">7</span>Gestion des budgets annuels</div>
        <p>
            Chaque société dispose d'un <strong>budget annuel alloué</strong> au début de l'exercice.
            Ce budget est renseigné par l'administrateur. Le système <strong>déduit automatiquement</strong>
            le montant de chaque DAP payée du budget de la société concernée.
        </p>

        <div class="info-box info-blue">
            <div class="info-icon">i</div>
            <div class="info-content">
                <strong>Consommation du budget</strong>
                <p>Le montant consommé est mis à jour au moment de l'<strong>enregistrement du paiement</strong>,
                pas à la validation. Une DAP validée mais non encore payée n'impacte pas encore le budget affiché.
                Le montant « Engagé en cours » sur votre tableau de bord représente ces validations en attente de paiement.</p>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><span class="section-num">8</span>Questions fréquentes</div>

        <p><strong>Q : Je ne vois pas une DAP dans ma liste à valider. Pourquoi ?</strong></p>
        <p style="margin-bottom:12px; color:#475569;">
            Soit la DAP a déjà été traitée (approuvée ou rejetée par vous), soit son montant est en dessous
            du seuil nécessitant votre intervention (ce qui n't'arrive pas pour le DF car vous validez tout).
            Vérifiez aussi que la DAP est bien au statut « En cours ».
        </p>

        <p><strong>Q : Puis-je revenir sur une validation déjà accordée ?</strong></p>
        <p style="margin-bottom:12px; color:#475569;">
            Non. Une fois une validation enregistrée dans le système, elle est définitive et traçable.
            En cas d'erreur, contactez l'administrateur pour annuler le dossier.
        </p>

        <p><strong>Q : Comment savoir si une DAP a été payée ?</strong></p>
        <p style="margin-bottom:12px; color:#475569;">
            La fiche DAP affiche le bloc <strong>« Paiement effectué »</strong> avec la date, le montant,
            le mode et la référence bancaire dès que l'administration enregistre le paiement.
            Le statut passe à <span class="tag tag-blue">Payée</span>.
        </p>

        <p><strong>Q : Le PDF d'une DAP est-il un document officiel ?</strong></p>
        <p style="margin-bottom:12px; color:#475569;">
            Oui. Le PDF généré reprend l'intégralité du dossier avec la chaîne de validation horodatée.
            Il peut servir de pièce justificative pour l'archivage comptable ou la banque.
        </p>

        <p><strong>Q : Que faire si le budget d'une société atteint 80 % avant la fin d'année ?</strong></p>
        <p style="color:#475569;">
            Le tableau de bord affiche une alerte rouge sur la carte de la société concernée. Il revient
            au DF de décider si les DAP restantes peuvent être approuvées ou si elles doivent être
            différées à l'exercice suivant. Aucun blocage automatique n'est appliqué par le système.
        </p>
    </div>

    <div class="info-box info-green" style="margin-top:10px;">
        <div class="info-icon">✓</div>
        <div class="info-content">
            <strong>Support et assistance</strong>
            <p>Pour toute question technique sur l'application, contactez l'administrateur système de Fortune Capital.
            Pour les questions liées aux procédures budgétaires, référez-vous à la politique interne du groupe.</p>
        </div>
    </div>

    <div class="page-footer">
        <span>Guide Utilisateur DAP — Direction Financière · Version 1.0 · Avril 2026</span>
        <span>Fortune Capital Group — Confidentiel</span>
    </div>
</div>

</body>
</html>
