# Admin — Utilisateurs

## Vue d'ensemble

Gestion complète des comptes utilisateurs : création, attribution des rôles, association à une boutique (demandeurs) ou à un niveau de validation (validateurs).

**Contrôleur** : `app/Http/Controllers/Admin/UserController.php`  
**Accès** : Admin uniquement  
**Pagination** : 15 utilisateurs par page

---

## Champs

| Champ | Type | Obligatoire | Description |
|-------|------|:-----------:|-------------|
| Nom | string | Oui | Nom complet |
| Email | string | Oui | Unique, sert d'identifiant de connexion |
| Mot de passe | string | Oui (création) | Haché automatiquement |
| Rôle | select | Oui | `admin`, `demandeur` ou `validateur` |
| Niveau de validation | select | Non | Uniquement pour les validateurs |
| Boutique | select | Non | Uniquement pour les demandeurs |

---

## Logique d'attribution par rôle

| Rôle | Boutique | Niveau de validation |
|------|----------|---------------------|
| `admin` | Ignoré | Ignoré |
| `demandeur` | Assignée | Non applicable |
| `validateur` | Non applicable | Assigné |

Si un utilisateur change de rôle (ex. de `demandeur` à `validateur`), l'admin doit mettre à jour les champs correspondants.

---

## Règles métier

- L'**email** est unique dans toute l'application.
- Le mot de passe n'est requis qu'à la **création**. En édition, laisser le champ vide conserve l'ancien mot de passe.
- Un admin **ne peut pas supprimer son propre compte** (protection 403).
- Seules les boutiques actives (`is_active = true`) apparaissent dans le sélecteur.
- Les niveaux de validation sont triés par ordre croissant.

---

## Données affichées dans la liste

- Nom, email
- Rôle (badge coloré : violet = admin, sky = demandeur, emerald = validateur)
- Niveau de validation (pour les validateurs)
- Boutique (pour les demandeurs)
- Date de création

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/users` | `admin.users.index` | Liste (paginée) |
| GET | `/admin/users/create` | `admin.users.create` | Formulaire création |
| POST | `/admin/users` | `admin.users.store` | Enregistrement |
| GET | `/admin/users/{id}/edit` | `admin.users.edit` | Formulaire édition |
| PUT | `/admin/users/{id}` | `admin.users.update` | Mise à jour |
| DELETE | `/admin/users/{id}` | `admin.users.destroy` | Suppression |
