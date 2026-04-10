# AchatPro — Documentation

Application de gestion des commandes d'achat avec circuit de validation multi-niveaux.

## Table des matières

| # | Module | Description |
| - | ------ | ----------- |
| 01 | [Authentification](01_authentification.md) | Connexion, rôles et contrôle d'accès |
| 02 | [Tableau de bord](02_tableau_de_bord.md) | Vues personnalisées par rôle |
| 03 | [Commandes d'achat](03_commandes_achat.md) | Création, édition, soumission, export |
| 04 | [Circuit de validation](04_circuit_validation.md) | Workflow multi-niveaux, approbation, refus |
| 05 | [Réceptions & livraisons](05_receptions_livraisons.md) | Suivi des réceptions partielles et complètes |
| 06 | [Audit & historique](06_audit_historique.md) | Journal des validations, export PDF/Excel |
| 07 | [Gestion budgétaire](07_gestion_budgetaire.md) | Budgets par boutique/catégorie, suivi en temps réel |
| 08 | [Admin — Boutiques](08_admin_boutiques.md) | Gestion des points de vente |
| 09 | [Admin — Utilisateurs](09_admin_utilisateurs.md) | Gestion des comptes et rôles |
| 10 | [Admin — Niveaux de validation](10_admin_niveaux_validation.md) | Configuration du circuit d'approbation |
| 11 | [Admin — Catégories](11_admin_categories.md) | Hiérarchie des catégories d'articles |
| 12 | [Admin — Articles](12_admin_articles.md) | Catalogue des articles commandables |
| 13 | [Admin — Fournisseurs](13_admin_fournisseurs.md) | Gestion des fournisseurs |
| 14 | [Notifications](14_notifications.md) | Alertes email et in-app |
| 15 | [Analytique](15_analytique.md) | Tableau de bord analytique avancé (admin) |
| 16 | [Délégation de validation](16_delegations_validation.md) | Validation par intérim, traçabilité audit |
| 17 | [Commentaires & négociation](17_commentaires_negociation.md) | Discussion, demande de révision, pièces jointes sur commande |
| 18 | [Intégration comptable](18_integration_comptable.md) | Écritures OHADA, export FEC/CSV, rapprochement facture/BC |

---

## Architecture rapide

```text
Demandeur        Validateur(s)          Admin
    │                  │                  │
    ▼                  ▼                  ▼
Créer commande → Valider par niveau → Confirmer commande
    │                  │                  │
    ▼                  │                  ▼
Soumettre ──────────► L1 ──► L2 ──► LN → Approuvée
    │                  │                  │
    │              (Refus) ◄──────────────┘
    │                  │
    ▼                  ▼
  (Resoumission)   Notifié
```

## Stack technique

- **Backend** : Laravel 11, PHP 8.2
- **Frontend** : Vue 3 + TypeScript + Inertia.js
- **UI** : Tailwind CSS + shadcn/vue
- **Base de données** : MySQL
- **Graphiques** : Chart.js via vue-chartjs
- **PDF** : DomPDF (via Laravel)
- **Excel** : PhpSpreadsheet (via Laravel Excel)
