# Intégration comptable

## Vue d'ensemble

Module de génération et d'export des écritures comptables. À chaque confirmation d'un bon de commande, le système génère automatiquement les écritures en partie double selon le plan comptable OHADA / SYSCOHADA. Les écritures sont exportables en **FEC** (Sage, Odoo) ou **CSV** (Epegase, Excel, import générique).

**Contrôleurs** :

- `app/Http/Controllers/Admin/AccountingController.php`
- `app/Services/AccountingService.php`

---

## Génération automatique des écritures

Les écritures sont générées dans `AccountingService::generateForOrder()` lors de l'appel à `markOrdered()`.

### Logique de génération

Pour chaque bon de commande confirmé :

```
Pour chaque ligne :
    DÉBIT  [compte de charge de la catégorie]  [sous-total ligne]
    (si catégorie sans compte → 60500 "Autres achats")

CRÉDIT [401xxx fournisseur]  [montant total BC]
```

### Idempotence

La génération est idempotente : si un BC possède déjà des écritures (re-génération manuelle future), elles sont supprimées et recréées.

---

## Mapping comptable OHADA par catégorie

Chaque catégorie d'article peut avoir un **code comptable OHADA** et un **libellé** configurés dans `Admin > Catégories`.

| Champ | Exemple | Description |
|-------|---------|-------------|
| `account_code` | `60400` | Numéro de compte OHADA (max 10 caractères) |
| `account_label` | `Achats de matériels` | Libellé affiché dans les exports |

Si aucun code n'est configuré sur la catégorie, le compte par défaut `60500` (Autres achats) est utilisé.

### Comptes fréquents OHADA

| Code | Libellé type |
|------|-------------|
| `60100` | Achats de marchandises |
| `60210` | Fournitures de bureau |
| `60400` | Achats de matériels |
| `60420` | Matériels informatiques |
| `60500` | Autres achats (défaut) |
| `60600` | Fournitures non stockées |
| `401000` | Fournisseurs (crédit) |

---

## Structure d'une écriture

### Table `accounting_entries`

| Champ | Type | Description |
|-------|------|-------------|
| `purchase_order_id` | FK | BC source |
| `entry_date` | date | Date de confirmation du BC |
| `journal_code` | string | `ACH` (journal des achats) |
| `piece_ref` | string | Numéro du BC (`BC-2026-00001`) |
| `account_code` | string | Numéro de compte OHADA |
| `account_label` | string | Libellé du compte |
| `aux_code` | string | Code auxiliaire (code fournisseur) |
| `aux_label` | string | Libellé auxiliaire (nom fournisseur) |
| `entry_label` | string | Libellé de l'écriture |
| `debit` | decimal | Montant au débit |
| `credit` | decimal | Montant au crédit |

---

## Page de consultation (Admin)

**Route** : `GET /admin/accounting`

### Fonctionnalités

- Liste paginée (50 lignes) de toutes les écritures
- Totaux débit / crédit avec indicateur d'équilibre
- Filtres : période, compte (préfixe), N° de pièce, boutique
- Référence facture fournisseur affichée quand le BC a été rapproché
- Export FEC et CSV respectant les filtres actifs

---

## Exports

### Format FEC (`.txt`)

Standard FEC (Fichier des Écritures Comptables), séparé par `|`.

Compatible avec : **Sage 100**, **Sage X3**, **Odoo** (import journal), et tout logiciel respectant la norme SYSCOHADA.

Colonnes : `JournalCode | JournalLib | EcritureNum | EcritureDate | CompteNum | CompteLib | CompAuxNum | CompAuxLib | PieceRef | PieceDate | EcritureLib | Debit | Credit`

Dates au format `YYYYMMDD`, montants avec virgule décimale.

### Format CSV (`.csv`)

Séparé par `;`, encodage UTF-8 avec BOM (compatible double-clic Excel).

Compatible avec : **Epegase**, **Excel**, tout outil d'import tabulaire.

Colonnes : `N° BC ; Date ; Journal ; Compte ; Libellé compte ; Code aux. ; Libellé aux. ; Libellé écriture ; Débit ; Crédit`

---

## Rapprochement facture / BC

Voir [Réceptions & Livraisons](05_receptions_livraisons.md#rapprochement-facture--bon-de-commande) pour le détail.

La boucle comptable complète est :

```
BC confirmé → écritures générées (débit charge / crédit fournisseur)
      ↓
Livraison reçue → réception enregistrée
      ↓
Facture fournisseur reçue → rattachée à la réception (N°, date, montant)
      ↓
Rapprochement : montant BC ≈ montant facture → statut "Facture rapprochée" ✅
```

---

## Droits d'accès

| Action | Admin |
|--------|:-----:|
| Consulter les écritures | ✅ |
| Exporter FEC / CSV | ✅ |
| (Génération automatique à la confirmation) | — |

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/accounting` | `admin.accounting.index` | Liste des écritures |
| GET | `/admin/accounting/export/fec` | `admin.accounting.export` | Export FEC |
| GET | `/admin/accounting/export/csv` | `admin.accounting.export` | Export CSV |
