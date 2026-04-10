# Tableau de bord

## Vue d'ensemble

Le tableau de bord s'adapte automatiquement au rôle de l'utilisateur connecté. Trois vues distinctes sont rendues par `DashboardController`.

---

## Vue Admin

**Accès** : rôle `admin`

### Indicateurs globaux (stat cards)
- **Total** : nombre de toutes les commandes
- **En attente** : commandes dans le circuit de validation
- **Approuvées** : commandes validées + montant total approuvé (FCFA)
- **Refusées** : commandes rejetées

### Cartes budget
- **Budget validé** : somme des montants des commandes approuvées
- **Budget en attente** : somme des montants des commandes en cours de validation

### Graphiques
- **Donut** : répartition Brouillon / En attente / Approuvées / Refusées
- **Barres (6 mois)** : évolution mensuelle du nombre de commandes par statut

### Performance par boutique
Tableau listant chaque boutique active avec :
- Nombre total de commandes
- Nombre en attente
- Nombre approuvées
- Montant validé

### Suivi budgétaire
Top 5 des budgets triés par pourcentage engagé, avec barre de progression colorée :
- **Vert** : < 80% engagé
- **Amber** : ≥ 80% (alerte)
- **Rouge** : > 100% (dépassé)

### Raccourcis admin
Liens rapides vers : Utilisateurs, Niveaux de validation, Boutiques.

---

## Vue Validateur

**Accès** : rôle `validateur`

### Bandeau intérim

Si le validateur a des **délégations actives reçues**, un bandeau bleu s'affiche indiquant pour qui il valide par intérim et jusqu'à quelle date. Lien rapide vers la page de gestion des délégations.

### Bandeau niveau
Affiche le niveau de validation assigné à l'utilisateur (ex : Niveau 2 / 3 — Direction).

### Indicateurs

- **À valider** : commandes en attente à son niveau (y compris les niveaux délégués actifs)
- **Approuvées par moi** : compteur cumulatif
- **Refusées par moi** : compteur cumulatif

### Graphiques
- **Donut** : ratio de ses décisions (approuvées vs refusées)
- **Encart d'urgence** : si des commandes sont en attente, bouton direct vers la file de validation

---

## Vue Demandeur

**Accès** : rôle `demandeur`

### Indicateurs personnels
- Total de ses commandes
- Brouillons, En attente, Approuvées, Refusées
- Budget approuvé (somme de ses commandes approuvées)
- Budget en attente

### Bandeau boutique
Affiche la boutique de rattachement si définie.

### Graphiques
- **Donut** : répartition de ses statuts
- **Barres (6 mois)** : évolution de ses commandes

### Commandes récentes
Liste des 8 dernières commandes avec statut, date et boutique.

---

## Contrôleur

`app/Http/Controllers/DashboardController.php`

```php
public function index(): Response
{
    if ($user->isAdmin())     return $this->adminDashboard();
    if ($user->canValidate()) return $this->validatorDashboard($user);
    return $this->demandeurDashboard($user);
}
```

## Route

| Méthode | URL | Nom |
|---------|-----|-----|
| GET | `/dashboard` | `dashboard` |
