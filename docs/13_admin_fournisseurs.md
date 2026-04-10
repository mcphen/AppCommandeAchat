# Admin — Fournisseurs

## Vue d'ensemble

Les fournisseurs sont les prestataires ou vendeurs auprès desquels les commandes sont passées. Chaque fournisseur doit être **approuvé** avant de pouvoir être sélectionné dans les commandes.

**Contrôleur** : `app/Http/Controllers/Admin/FournisseurController.php`  
**Accès** : Admin uniquement

---

## Champs

| Champ | Type | Obligatoire | Contrainte | Description |
|-------|------|:-----------:|------------|-------------|
| Nom | string (255) | Oui | — | Raison sociale |
| Code | string (50) | Oui | Unique | Identifiant court (ex : `FOUR-001`) |
| Email | string | Non | — | Adresse email de contact |
| Téléphone | string | Non | — | Numéro de téléphone |
| Adresse | string (255) | Non | — | Adresse postale |
| Ville | string (120) | Non | — | Ville |
| Actif | boolean | Oui | — | Visibilité dans les formulaires |
| Approuvé | boolean | Oui | — | Autorisation de commander auprès de ce fournisseur |

---

## Double flag : actif vs approuvé

| `is_active` | `is_approved` | Comportement |
|:-----------:|:-------------:|--------------|
| ✅ | ✅ | Sélectionnable dans les commandes |
| ✅ | ❌ | Visible mais non sélectionnable |
| ❌ | — | Masqué de tous les formulaires |

- `is_active` : le fournisseur existe dans le système (visible ou non)
- `is_approved` : le fournisseur est homologué pour recevoir des commandes

---

## Règles métier

- Le **code** est unique dans toute l'application.
- Seuls les fournisseurs `is_active = true` ET `is_approved = true` apparaissent dans les listes déroulantes des commandes (fournisseur principal et fournisseur par ligne).

---

## Utilisation dans les commandes

Un fournisseur peut être renseigné à deux niveaux :
1. **Fournisseur principal** de la commande (`purchase_orders.fournisseur_id`)
2. **Fournisseur par ligne** (`purchase_order_lines.fournisseur_id`) — permet de panacher plusieurs fournisseurs au sein d'une même commande

---

## Fiche fournisseur (détail)

La page de détail d'un fournisseur affiche :
- Informations de contact
- **Statistiques d'utilisation** :
  - Nombre de lignes de commande
  - Montant total des commandes approuvées (`budget_approved`)
  - Montant des commandes en attente (`budget_pending`)

---

## Données affichées dans la liste

- Code, Nom, Ville
- Email, Téléphone
- Nombre de lignes de commande utilisées
- Statuts Actif / Approuvé (badges)

La liste est **filtrée par recherche** (nom, code, ville).

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/fournisseurs` | `admin.fournisseurs.index` | Liste |
| GET | `/admin/fournisseurs/create` | `admin.fournisseurs.create` | Formulaire création |
| POST | `/admin/fournisseurs` | `admin.fournisseurs.store` | Enregistrement |
| GET | `/admin/fournisseurs/{id}` | `admin.fournisseurs.show` | Fiche détail |
| GET | `/admin/fournisseurs/{id}/edit` | `admin.fournisseurs.edit` | Formulaire édition |
| PUT | `/admin/fournisseurs/{id}` | `admin.fournisseurs.update` | Mise à jour |
| DELETE | `/admin/fournisseurs/{id}` | `admin.fournisseurs.destroy` | Suppression |
