# Gestion budgétaire

## Vue d'ensemble

Le module budgétaire permet de définir des enveloppes financières par **boutique** et/ou par **catégorie d'articles**, sur une période annuelle ou mensuelle. La consommation est calculée en temps réel à partir des commandes en cours.

**Contrôleur** : `app/Http/Controllers/Admin/BudgetController.php`  
**Service** : `app/Services/BudgetService.php`  
**Accès** : Admin uniquement

---

## Concepts clés

### Périmètre d'un budget

Un budget peut cibler 4 périmètres différents :

| Boutique | Catégorie | Périmètre couvert |
|----------|-----------|-------------------|
| Définie | Définie | Dépenses de cette catégorie dans cette boutique |
| Définie | Vide | Total des dépenses de cette boutique (toutes catégories) |
| Vide | Définie | Total des dépenses de cette catégorie (toutes boutiques) |
| Vide | Vide | Budget global de toute l'entreprise |

### Méthode de calcul

Le **montant engagé** est la somme de deux composantes :

```
Engagé = Consommé + En validation

Consommé    = Montant des commandes entièrement approuvées (statut "approved")
En validation = Montant des commandes en cours de validation (statut "pending")
Disponible  = Alloué − Engagé  (plancher à 0)
```

**Pour les budgets par boutique** (sans catégorie) : le calcul se fait sur le champ `amount` de la commande entière.

**Pour les budgets par catégorie** : le calcul se fait au niveau des **lignes de commande** (`quantité × prix unitaire`) dont l'article appartient à la catégorie cible.

### Périodes

- **Annuel** (`month = null`) : couvre toutes les commandes de l'année, quelle que soit leur date
- **Mensuel** (`month = 1..12`) : couvre uniquement les commandes créées ce mois-là

---

## Alertes

| Seuil | Statut | Affichage |
|-------|--------|-----------|
| < 80% engagé | OK | Barre verte |
| ≥ 80% engagé | Alerte | Barre amber + badge "Alerte" |
| > 100% engagé | Dépassé | Barre rouge + badge "Dépassé" + montant du dépassement |

---

## Interface — Liste des budgets

**Route** : `GET /admin/budgets?year={année}`

- Filtre par année (sélecteur en haut de page)
- Vue en **cards** : une carte par budget
- Chaque carte affiche :
  - Périmètre (boutique + catégorie, ou "Toutes boutiques" / "Toutes catégories")
  - Période (ex : "Annuel 2026" ou "Avril 2026")
  - Montant alloué, consommé, en validation
  - Barre de progression colorée
  - Message d'alerte si dépassement ou seuil critique
- **Résumé global** en haut : total alloué, total engagé, budgets en alerte, budgets dépassés

---

## Création / Edition d'un budget

**Routes** : `GET /admin/budgets/create`, `GET /admin/budgets/{id}/edit`

### Champs

| Champ | Type | Obligatoire | Description |
|-------|------|:-----------:|-------------|
| Boutique | select | Non | Laisser vide = toutes les boutiques |
| Catégorie | select | Non | Laisser vide = toutes les catégories |
| Année | select | Oui | Exercice fiscal (2020 – 2050) |
| Périodicité | select | Non | Annuel (défaut) ou mois spécifique |
| Montant alloué | number | Oui | En FCFA, minimum 1 |
| Notes internes | textarea | Non | Justification, décision de comité |

**Aperçu dynamique** : en haut du formulaire, un bandeau affiche en temps réel le périmètre sélectionné (ex : "Boutique Centrale · Fournitures de bureau").

**Unicité** : un seul budget peut exister par combinaison boutique / catégorie / année / mois.

---

## Suppression

**Route** : `DELETE /admin/budgets/{id}`  
Suppression directe, sans restriction (aucune dépendance fonctionnelle).

---

## Intégration dans le tableau de bord

Le tableau de bord **admin** affiche une section "Suivi budgétaire" avec les **5 budgets** les plus critiques (triés par % engagé décroissant), avec barre de progression colorée et lien vers la liste complète.

---

## Modèle de données

Table `budgets` :

| Champ | Type | Description |
|-------|------|-------------|
| `id` | bigint | Clé primaire |
| `boutique_id` | FK nullable | Boutique cible (null = toutes) |
| `category_id` | FK nullable | Catégorie cible (null = toutes) |
| `year` | smallint | Année de l'exercice |
| `month` | tinyint nullable | Mois (1–12), null = annuel |
| `amount` | decimal(15,2) | Montant alloué |
| `notes` | text | Notes internes |
| `created_by` | FK (users) | Admin ayant créé le budget |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/budgets` | `admin.budgets.index` | Liste avec filtrage année |
| GET | `/admin/budgets/create` | `admin.budgets.create` | Formulaire création |
| POST | `/admin/budgets` | `admin.budgets.store` | Enregistrement |
| GET | `/admin/budgets/{id}/edit` | `admin.budgets.edit` | Formulaire édition |
| PUT | `/admin/budgets/{id}` | `admin.budgets.update` | Mise à jour |
| DELETE | `/admin/budgets/{id}` | `admin.budgets.destroy` | Suppression |
