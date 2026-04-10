# Audit & Historique

## Vue d'ensemble

Le module d'audit offre une traçabilité complète de toutes les décisions de validation. Chaque approbation ou refus est consigné avec son auteur, son horodatage, et le commentaire associé. Le journal peut être exporté en PDF ou Excel.

**Contrôleur** : `app/Http/Controllers/AuditController.php`

---

## Accès

| Rôle | Ce qu'il voit |
|------|---------------|
| Admin | Toutes les actions de tous les validateurs |
| Validateur | Uniquement ses propres actions |

---

## Informations affichées

Pour chaque entrée du journal :

| Colonne | Description |
|---------|-------------|
| Date | Date et heure de l'action |
| Commande | Titre et numéro de la commande |
| Boutique | Boutique de la commande |
| Demandeur | Utilisateur ayant créé la commande |
| Montant | Montant total (FCFA) |
| Action | `Approuvée` (vert) ou `Refusée` (rouge) |
| Niveau | Niveau de validation concerné (L1, L2…) |
| Validateur | Nom du validateur ayant agi |
| Commentaire | Raison du refus (si applicable) |
| Intérim pour | *(si délégation)* Nom du validateur titulaire pour le compte duquel l'action a été effectuée |

> Les validations effectuées par délégation ont un `delegated_by_id` non nul, permettant de distinguer les actions directes des actions par intérim. Voir [Délégation de validation](16_delegations_validation.md).

---

## Statistiques en temps réel

En haut de page, 4 indicateurs calculés sur la plage filtrée :
- **Total** : nombre total d'actions
- **Approuvées** : nombre d'approbations
- **Refusées** : nombre de refus
- **Commandes touchées** : nombre de commandes distinctes ayant eu au moins une action

---

## Filtres disponibles

- **Action** : toutes / approuvées / refusées
- **Boutique** : filtre par boutique de la commande
- **Niveau de validation** : filtre par L1, L2…
- **Validateur** *(admin uniquement)* : filtre par utilisateur
- **Date de** / **Date à** : plage de dates
- **Recherche** : texte libre sur le titre de la commande

**Pagination** : 20 entrées par page

---

## Export PDF

**Route** : `GET /audit/export/pdf`

Caractéristiques du rapport :
- Orientation **paysage** (A4)
- En-tête avec le résumé des statistiques (total, approuvées, refusées, commandes touchées)
- Tableau complet des actions avec les mêmes filtres que la vue
- Nom du fichier : `audit-validations-YYYY-MM-DD.pdf`

---

## Export Excel

**Route** : `GET /audit/export/excel`

Structure du fichier :
1. **En-tête** : "RAPPORT D'AUDIT — VALIDATIONS"
2. **Section résumé** : statistiques (total, approuvées, refusées, commandes touchées, date de génération)
3. **Tableau de données** avec 9 colonnes :
   - Date, Commande, Boutique, Demandeur, Montant, Action, Niveau, Validateur, Commentaire
4. **Mise en forme** :
   - En-têtes en bleu avec texte blanc en gras
   - Lignes alternées (fond clair / fond très clair)
   - Cellule "Action" colorée : vert pour approuvée, rouge pour refusée
   - Colonnes auto-dimensionnées

Nom du fichier : `audit-validations-YYYY-MM-DD.xlsx`

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/audit` | `audit.index` | Journal des validations |
| GET | `/audit/export/pdf` | `audit.export` | Export PDF |
| GET | `/audit/export/excel` | `audit.export` | Export Excel |
