# Admin — Catégories

## Vue d'ensemble

Les catégories permettent d'organiser le catalogue d'articles en une **hiérarchie à deux niveaux** (catégorie parente → sous-catégorie). Elles sont utilisées dans les budgets et pour la classification des articles.

**Contrôleur** : `app/Http/Controllers/Admin/CategoryController.php`  
**Accès** : Admin uniquement

---

## Champs

| Champ | Type | Obligatoire | Description |
|-------|------|:-----------:|-------------|
| Nom | string (255) | Oui | Nom de la catégorie |
| Catégorie parente | select | Non | Permet de créer une sous-catégorie |
| Description | text | Non | Précisions sur le périmètre |
| Actif | boolean | Oui | Contrôle la visibilité dans les formulaires |

---

## Hiérarchie

La structure est à **deux niveaux** :

```
📁 Informatique
   ├── 📄 Ordinateurs
   ├── 📄 Accessoires
   └── 📄 Logiciels
📁 Fournitures de bureau
   ├── 📄 Papeterie
   └── 📄 Mobilier
```

- Une catégorie **racine** a `parent_id = null`
- Une **sous-catégorie** référence sa catégorie parente via `parent_id`
- L'attribut `full_name` combine les deux niveaux : `"Informatique > Ordinateurs"`

---

## Règles métier

- `is_active = false` masque la catégorie dans les listes déroulantes (formulaires articles, budgets), mais ne supprime pas les données existantes.
- Une catégorie avec des **articles rattachés** peut toujours être supprimée (aucune protection) — attention à réaffecter les articles d'abord si nécessaire.

---

## Impact sur les autres modules

| Module | Utilisation |
|--------|-------------|
| Articles | Chaque article peut être rattaché à une catégorie |
| Budgets | Un budget peut cibler une catégorie (calcul sur les lignes de commande) |
| Commandes | Filtre possible par catégorie d'article dans les rapports |

---

## Données affichées dans la liste

- Nom complet (`full_name` : "Parent > Enfant")
- Catégorie parente
- Description
- Nombre d'articles rattachés
- Statut (Actif / Inactif)

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/categories` | `admin.categories.index` | Liste |
| GET | `/admin/categories/create` | `admin.categories.create` | Formulaire création |
| POST | `/admin/categories` | `admin.categories.store` | Enregistrement |
| GET | `/admin/categories/{id}/edit` | `admin.categories.edit` | Formulaire édition |
| PUT | `/admin/categories/{id}` | `admin.categories.update` | Mise à jour |
| DELETE | `/admin/categories/{id}` | `admin.categories.destroy` | Suppression |
