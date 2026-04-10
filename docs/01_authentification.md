# Authentification & Contrôle d'accès

## Vue d'ensemble

L'authentification est gérée par Laravel Breeze/Fortify. Chaque utilisateur se connecte avec son email et mot de passe, et se voit attribuer un **rôle** qui détermine l'ensemble de ses droits dans l'application.

## Rôles disponibles

| Rôle | Slug | Description |
|------|------|-------------|
| Administrateur | `admin` | Accès total à toutes les fonctionnalités |
| Demandeur | `demandeur` | Crée et suit ses propres commandes |
| Validateur | `validateur` | Approuve ou refuse les commandes à son niveau |

## Matrice des permissions

| Fonctionnalité | Admin | Validateur | Demandeur |
|----------------|:-----:|:----------:|:---------:|
| Tableau de bord global | ✅ | — | — |
| Voir ses propres commandes | ✅ | — | ✅ |
| Voir toutes les commandes | ✅ | — | — |
| Créer une commande | ✅ | — | ✅ |
| Soumettre pour validation | ✅ | — | ✅ |
| Valider / Refuser | ✅ | ✅ | — |
| Confirmer commande (markOrdered) | ✅ | — | — |
| Saisir une réception | ✅ | — | ✅ |
| Voir l'audit | ✅ | ✅ (ses actions) | — |
| Administration | ✅ | — | — |

## Middleware de protection

Les routes sont protégées par un middleware `role` paramétrable :

```php
Route::middleware('role:admin')->group(...)           // admin uniquement
Route::middleware('role:validateur,admin')->group(...) // validateur ou admin
Route::middleware('role:demandeur,admin')->group(...)  // demandeur ou admin
```

## Utilisateur et boutique

- Un **demandeur** est rattaché à une **boutique** (`boutique_id`). Ses commandes héritent automatiquement de sa boutique.
- Un **validateur** est rattaché à un **niveau de validation** (`validation_level_id`), pas à une boutique.
- Un **admin** n'a pas de boutique ni de niveau de validation imposés.

## Modèle User

```
User
 ├── role_id              → Role (admin / demandeur / validateur)
 ├── boutique_id          → Boutique (null sauf pour demandeur)
 ├── validation_level_id  → ValidationLevel (null sauf pour validateur)
 └── email_verified_at    → Vérification email (optionnelle)
```

## Routes

| Méthode | URL | Action |
|---------|-----|--------|
| GET | `/login` | Affiche le formulaire de connexion |
| POST | `/login` | Authentifie l'utilisateur |
| POST | `/logout` | Déconnexion |
