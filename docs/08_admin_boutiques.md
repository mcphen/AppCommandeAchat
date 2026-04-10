# Admin — Boutiques

## Vue d'ensemble

Les boutiques représentent les unités opérationnelles (points de vente, agences, sites) de l'organisation. Chaque commande et chaque demandeur est rattaché à une boutique.

**Contrôleur** : `app/Http/Controllers/Admin/BoutiqueController.php`  
**Accès** : Admin uniquement

---

## Champs

| Champ | Type | Obligatoire | Contrainte | Description |
|-------|------|:-----------:|------------|-------------|
| Code | string (50) | Oui | Unique | Identifiant court (ex : `BT-DAKAR`) |
| Nom | string (255) | Oui | — | Nom complet de la boutique |
| Adresse | string (255) | Non | — | Adresse physique |
| Ville | string (120) | Non | — | Ville |
| Actif | boolean | Oui | — | Contrôle la visibilité dans les formulaires |

---

## Règles métier

- Le **code** est unique. Deux boutiques ne peuvent pas avoir le même code.
- Le flag `is_active` contrôle si la boutique apparaît dans les listes déroulantes lors de la création de commandes et d'utilisateurs. Désactiver une boutique ne supprime pas ses données.
- **Suppression impossible** si :
  - Des utilisateurs sont rattachés à cette boutique
  - Des commandes d'achat existent pour cette boutique

---

## Données affichées dans la liste

- Code, Nom, Ville
- Nombre d'utilisateurs rattachés
- Nombre de commandes enregistrées
- Statut (Actif / Inactif)

---

## Impact sur les autres modules

| Module | Lien avec les boutiques |
|--------|------------------------|
| Utilisateurs | Chaque demandeur est rattaché à une boutique |
| Commandes d'achat | Chaque commande hérite la boutique de son créateur |
| Budgets | Un budget peut être défini par boutique |
| Validations | Filtre boutique dans la file d'attente |
| Audit | Filtre boutique dans le journal |
| Tableau de bord admin | Statistiques par boutique |

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/boutiques` | `admin.boutiques.index` | Liste |
| GET | `/admin/boutiques/create` | `admin.boutiques.create` | Formulaire création |
| POST | `/admin/boutiques` | `admin.boutiques.store` | Enregistrement |
| GET | `/admin/boutiques/{id}/edit` | `admin.boutiques.edit` | Formulaire édition |
| PUT | `/admin/boutiques/{id}` | `admin.boutiques.update` | Mise à jour |
| DELETE | `/admin/boutiques/{id}` | `admin.boutiques.destroy` | Suppression |
