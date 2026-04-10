# Délégation de validation

## Vue d'ensemble

Le module de délégation permet à un validateur (ou à l'administrateur) de confier temporairement ses droits de validation à un autre utilisateur, pour une période définie. Les validations effectuées par intérim sont entièrement tracées dans l'audit.

**Contrôleur** : `app/Http/Controllers/DelegationController.php`  
**Modèle** : `app/Models/ValidationDelegation.php`  
**Accès** : rôles `validateur` et `admin`

---

## Problème résolu

Sans délégation, les commandes en attente sont **bloquées** si le validateur assigné à un niveau est absent. Ce module permet de transférer temporairement les droits de validation sans modifier la configuration permanente du circuit.

---

## Principe de fonctionnement

```
Validateur absent (délégateur)
       │
       │  Crée une délégation
       │  - Niveau délégué
       │  - Délégataire (n'importe quel utilisateur actif)
       │  - Période : date début → date fin
       │  - Motif (optionnel)
       ▼
Délégataire notifié (email + notification in-app)
       │
       ▼
Durant la période active :
Le délégataire voit et peut valider les commandes
au niveau délégué, exactement comme le validateur
       │
       ▼
Chaque validation trace "delegated_by_id" dans validation_logs
→ Visible dans l'Audit & Historique
```

---

## Règles métier

| Règle | Détail |
|-------|--------|
| Qui peut créer | Admin (pour n'importe quel niveau) ou Validateur (son propre niveau uniquement) |
| Qui peut recevoir | N'importe quel utilisateur actif (même un demandeur) |
| Délégation à soi-même | Interdite (validation côté serveur) |
| Délégation partielle | Oui — on délègue un niveau spécifique, pas tous les droits |
| Plusieurs délégations simultanées | Autorisé — un utilisateur peut valider à plusieurs niveaux par délégation |
| Désactivation manuelle | Le délégateur ou l'admin peut désactiver avant l'échéance |
| Suppression | Le délégateur ou l'admin peut supprimer la délégation |

---

## Statuts d'une délégation

| Statut | Condition |
|--------|-----------|
| **Active** | `is_active = true` et dans la période |
| **À venir** | `is_active = true` et `starts_at` dans le futur |
| **Expirée** | `ends_at` passé |
| **Désactivée** | `is_active = false` manuellement |

Seules les délégations **Active** donnent des droits de validation effectifs.

---

## Traçabilité dans l'audit

Chaque `ValidationLog` dispose d'un champ `delegated_by_id` (nullable) :
- `null` → validation effectuée par le validateur titulaire
- `user_id` → validation effectuée par intérim pour le compte du validateur indiqué

Le `AuditController` charge cette relation (`delegatedBy`) automatiquement.

---

## Tableau de bord — Banner intérim

Si un utilisateur a des délégations actives **reçues**, un bandeau bleu s'affiche en haut de son tableau de bord :

> *"Vous validez par intérim pour : [Nom] — niveau [Niveau] jusqu'au [Date]"*

Même bandeau disponible sur la page de gestion des délégations.

---

## Notification

À la création d'une délégation, le délégataire reçoit automatiquement :
- Une **notification in-app** visible dans la cloche
- Un **email** avec les détails (délégateur, niveau, période, motif)

**Classe** : `app/Notifications/DelegationReceivedNotification.php`

---

## Page de gestion

**Route** : `GET /delegations` — `delegations.index`

La page est organisée en deux onglets :

### Onglet "Délégations données"
Liste de toutes les délégations créées par l'utilisateur (ou toutes pour l'admin).  
Colonnes : délégataire, niveau, période, motif, statut, actions (activer/désactiver, supprimer).

### Onglet "Délégations reçues"
Liste des délégations dont l'utilisateur est le bénéficiaire.  
Colonnes : délégateur, niveau, période, motif, statut. *(lecture seule)*

### Formulaire de création (inline)
- Sélection du délégataire parmi tous les utilisateurs actifs
- Sélection du niveau de validation (admin : tous les niveaux ; validateur : son niveau uniquement)
- Date de début et date de fin
- Motif (optionnel)

---

## Schéma base de données

### Table `validation_delegations`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `delegator_id` | FK → users | Validateur qui délègue |
| `delegatee_id` | FK → users | Utilisateur qui reçoit les droits |
| `validation_level_id` | FK → validation_levels | Niveau délégué |
| `starts_at` | date | Début de la période |
| `ends_at` | date | Fin de la période |
| `reason` | string nullable | Motif de l'absence |
| `is_active` | boolean | Activation manuelle |
| `created_at` / `updated_at` | timestamps | — |

### Ajout sur `validation_logs`

| Colonne | Type | Description |
|---------|------|-------------|
| `delegated_by_id` | FK → users (nullable) | Validateur titulaire si validation par intérim |

---

## Méthodes clés sur le modèle `User`

| Méthode | Description |
|---------|-------------|
| `delegationsGiven()` | Toutes les délégations créées par cet utilisateur |
| `delegationsReceived()` | Toutes les délégations reçues |
| `activeDelegationsReceived()` | Délégations actives et dans la période courante |
| `validatableLevelOrders()` | Tableau des ordres de niveau validables (propre + délégués) |
| `getDelegatorIdForLevel(int $order)` | Retourne le délégateur si la validation est par intérim |

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/delegations` | `delegations.index` | Liste + formulaire |
| POST | `/delegations` | `delegations.store` | Créer une délégation |
| PATCH | `/delegations/{id}/toggle` | `delegations.toggle` | Activer / Désactiver |
| DELETE | `/delegations/{id}` | `delegations.destroy` | Supprimer |
