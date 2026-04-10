# Admin — Articles

## Vue d'ensemble

Le catalogue d'articles regroupe tous les produits ou services pouvant être commandés. Les articles sont sélectionnables dans les lignes de commande et leur prix unitaire est pré-rempli automatiquement (mais reste modifiable).

**Contrôleur** : `app/Http/Controllers/Admin/ArticleController.php`  
**Accès** : Admin uniquement

---

## Champs

| Champ | Type | Obligatoire | Contrainte | Description |
|-------|------|:-----------:|------------|-------------|
| Nom | string (255) | Oui | — | Libellé de l'article |
| Référence | string (100) | Non | Unique | Code article interne (ex : `INF-001`) |
| Catégorie | select | Non | — | Classification dans la hiérarchie |
| Unité | select | Oui | — | Unité de mesure |
| Prix unitaire de référence | decimal | Non | ≥ 0 | Valeur indicative, modifiable à la commande |
| Description | text (1000) | Non | — | Caractéristiques, spécifications |
| Actif | boolean | Oui | — | Visibilité dans les formulaires de commande |

---

## Unités disponibles

`pièce` · `kg` · `litre` · `mètre` · `boîte` · `carton` · `heure` · `forfait` · `lot`

---

## Règles métier

- La **référence** est unique (si renseignée). Deux articles ne peuvent pas avoir la même référence.
- Le **prix unitaire** est indicatif : il pré-remplit le champ lors de l'ajout en commande, mais le demandeur peut le modifier ligne par ligne.
- `is_active = false` masque l'article dans la liste déroulante des commandes, mais les lignes de commande existantes restent intactes.
- **Suppression impossible** si l'article est référencé dans des lignes de commande existantes.

---

## Utilisation dans les commandes

Lors de la création d'une commande, le demandeur :
1. Sélectionne un article dans le catalogue
2. Le prix unitaire est pré-rempli depuis `articles.unit_price`
3. Renseigne la quantité
4. Peut modifier le prix et ajouter un fournisseur par ligne

Le montant de la ligne = `quantité × prix unitaire`.

---

## Données affichées dans la liste

- Nom, Référence
- Catégorie (avec hiérarchie)
- Unité
- Prix de référence
- Nombre de fois utilisé dans des commandes (`order_lines_count`)
- Statut (Actif / Inactif)

La liste est **filtrée par recherche** (nom, référence, catégorie).

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/articles` | `admin.articles.index` | Liste |
| GET | `/admin/articles/create` | `admin.articles.create` | Formulaire création |
| POST | `/admin/articles` | `admin.articles.store` | Enregistrement |
| GET | `/admin/articles/{id}/edit` | `admin.articles.edit` | Formulaire édition |
| PUT | `/admin/articles/{id}` | `admin.articles.update` | Mise à jour |
| DELETE | `/admin/articles/{id}` | `admin.articles.destroy` | Suppression |
