# Tableau de bord analytique

## Vue d'ensemble

Le module analytique offre des indicateurs avancés de performance et de pilotage, réservés à l'administrateur. Il est accessible via le menu **Analytique** dans la barre latérale (section dédiée entre Navigation et Administration).

**Contrôleur** : `app/Http/Controllers/AnalyticsController.php`  
**Route** : `GET /analytics` — `analytics.index`  
**Accès** : rôle `admin` uniquement

---

## Sections du tableau de bord

### 1. Commandes bloquées

Alerte automatique listant les commandes en statut `pending` depuis plus de **3 jours**.

| Colonne | Description |
|---------|-------------|
| Commande | Titre + date de soumission |
| Demandeur | Nom de l'utilisateur ayant soumis |
| Boutique | Boutique de rattachement |
| Montant | Montant de la commande |
| Attente | Nombre de jours en attente (badge rouge si ≥ 7j) |
| Action | Lien direct vers la page de validation |

> Si aucune commande n'est bloquée, un message de confirmation s'affiche.

---

### 2. Top 5 fournisseurs

Classement des 5 fournisseurs générant le plus de montant sur les commandes **approuvées**, calculé via les lignes de commande (`quantity × unit_price`).

Affichage :
- Graphique en barres horizontales (dégradé indigo)
- Liste classée avec rang, nom, nombre de commandes, montant total

---

### 3. Top 5 catégories

Même logique que les fournisseurs, mais regroupé par **catégorie d'article** (via `purchase_order_lines → articles → categories`).

Affichage :
- Graphique en barres horizontales (dégradé vert)
- Liste classée avec rang, nom, nombre de commandes, montant total

---

### 4. Dépenses mensuelles par boutique

Courbe multi-séries sur les **6 derniers mois**, montrant le montant total des commandes **approuvées** pour chaque boutique active.

- Chaque boutique = une courbe colorée distincte
- Axe Y : montants en FCFA (format court : K, M, Md)
- Légende en bas

---

### 5. Délai moyen de validation

#### Par niveau
Graphique en barres + tableau récapitulatif.  
Calcul : `AVG(TIMESTAMPDIFF(HOUR, purchase_orders.submitted_at, validation_logs.created_at))` groupé par niveau.

| Colonne | Description |
|---------|-------------|
| Niveau | Nom du niveau de validation |
| Délai moyen | En jours (arrondi à 1 décimale) |
| Total | Nombre de validations effectuées |

#### Par validateur
Tableau ordonné du plus rapide au plus lent.  
Pour chaque validateur : délai moyen, nombre d'approuvées, nombre de refusées, total.

---

### 6. Taux de refus par demandeur

Tableau des 10 demandeurs avec le taux de refus le plus élevé, calculé sur les commandes finalisées (`approved` ou `rejected`).

| Colonne | Description |
|---------|-------------|
| Demandeur | Nom avec initiale |
| Total | Commandes finalisées |
| Approuvées | Nombre approuvées |
| Refusées | Nombre refusées |
| Taux refus | Pourcentage avec barre de progression colorée |

Codes couleur du taux :
- **Vert** : < 25%
- **Amber** : entre 25% et 50%
- **Rouge** : ≥ 50%

---

## Données techniques

| Méthode contrôleur | Description | Source principale |
|--------------------|-------------|-------------------|
| `getBlockedOrders()` | Commandes pending > 3j | `purchase_orders` |
| `getTopFournisseurs()` | Top 5 fournisseurs | `purchase_order_lines` + `fournisseurs` |
| `getTopCategories()` | Top 5 catégories | `purchase_order_lines` + `articles` + `categories` |
| `getMonthlyByBoutique()` | Courbe dépenses 6 mois | `purchase_orders` + `boutiques` |
| `getValidationDelays()` | Délai moyen | `validation_logs` + `purchase_orders` |
| `getRejectionRates()` | Taux de refus | `purchase_orders` + `users` |

---

## Route

| Méthode | URL | Nom | Accès |
|---------|-----|-----|-------|
| GET | `/analytics` | `analytics.index` | Admin |
