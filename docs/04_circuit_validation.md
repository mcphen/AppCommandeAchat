# Circuit de validation

## Vue d'ensemble

Le circuit de validation est un workflow multi-niveaux séquentiel. Une commande soumise doit être approuvée par chaque niveau de validation, dans l'ordre, avant d'être définitivement validée.

**Contrôleur** : `app/Http/Controllers/ValidationController.php`

---

## Principe du workflow

```text
Commande soumise
       │
       ▼
  Niveau 1 (L1)
  Validateurs L1 notifiés
       │
   ┌───┴────────────────┐
   ▼                    ▼
Approuvée             Refusée ──► Créateur notifié
   │                              Statut → "rejected"
   ▼
  Niveau 2 (L2)
  Validateurs L2 notifiés
   │
  ...
   │
   ▼
  Niveau N (dernier)
   │
   ▼
Commande définitivement approuvée
Créateur notifié → statut "approved"
```

---

## Rôles et droits

| Action | Admin | Validateur | Délégataire actif |
| ------ | :---: | :--------: | :---------------: |
| Voir toutes les commandes en attente | ✅ | ❌ (son niveau uniquement) | ❌ (niveau délégué uniquement) |
| Voir les commandes à son niveau | ✅ | ✅ | ✅ |
| Approuver | ✅ | ✅ (son niveau uniquement) | ✅ (niveau délégué uniquement) |
| Refuser | ✅ | ✅ (son niveau uniquement) | ✅ (niveau délégué uniquement) |

Un validateur qui n'a pas de `validation_level_id` assigné **et** aucune délégation active reçue ne peut pas accéder aux validations (403).

> **Délégation** : un utilisateur peut recevoir temporairement les droits d'un niveau via le module [Délégation de validation](16_delegations_validation.md). Les niveaux délégués actifs sont pris en compte exactement comme le niveau propre du validateur.

---

## Approbation

**Route** : `POST /validations/{id}/approve`

**Règles** :

- La commande doit être en statut `pending`
- Le niveau en cours (`current_level_order`) doit correspondre au niveau du validateur
- Un `ValidationLog` est créé (action = `approved`)

**Comportement** :

- S'il existe un niveau suivant → `current_level_order` passe au niveau suivant, les validateurs du niveau suivant reçoivent une notification
- Si c'est le dernier niveau → statut passe à `approved`, le créateur est notifié

---

## Refus

**Route** : `POST /validations/{id}/reject`

**Règles** :

- Mêmes conditions que l'approbation
- **Le commentaire de refus est obligatoire**
- Un `ValidationLog` est créé (action = `rejected`)

**Comportement** :

- Statut passe à `rejected`, `current_level_order` remis à `null`
- Le créateur est notifié avec le commentaire de refus
- Le créateur peut re-éditer et re-soumettre la commande

---

## Demande de révision

Avant de prendre une décision formelle, un validateur peut **demander une révision** via le panneau de discussion intégré à la page de validation.

**Effet** : le statut de la commande passe à `needs_revision`. Le demandeur est notifié, peut modifier la commande et la re-soumettre. La re-soumission reprend **au niveau exact** qui a demandé la révision — les niveaux précédents déjà validés ne sont pas remis en cause.

Ce mécanisme évite le rejet formel pour des motifs mineurs (document manquant, montant à ajuster).

> Voir [Commentaires & négociation](17_commentaires_negociation.md) pour le détail complet.

---

## Historique de validation

Chaque action (approbation ou refus) est enregistrée dans la table `validation_logs` :

| Champ | Description |
| ----- | ----------- |
| `purchase_order_id` | Commande concernée |
| `validation_level_id` | Niveau de validation |
| `user_id` | Validateur ayant agi |
| `action` | `approved` ou `rejected` |
| `comment` | Commentaire (obligatoire pour les refus) |
| `delegated_by_id` | Validateur titulaire si action par intérim (nullable) |
| `created_at` | Date et heure de l'action |

Cet historique est visible dans le détail de chaque commande et dans l'écran [Audit & Historique](06_audit_historique.md).

---

## File de validation (liste)

**Route** : `GET /validations`

**Pour l'admin** : toutes les commandes `pending`, triées par date de soumission (plus récent en premier).

**Pour le validateur** : seulement les commandes dont `current_level_order` correspond à son niveau (y compris les niveaux délégués actifs).

**Filtre boutique** disponible (paramètre `boutique_id` dans l'URL).

---

## Routes

| Méthode | URL | Nom | Action |
| ------- | --- | --- | ------ |
| GET | `/validations` | `validations.index` | File d'attente |
| GET | `/validations/{id}` | `validations.show` | Détail avec historique et discussion |
| POST | `/validations/{id}/approve` | `validations.approve` | Approbation |
| POST | `/validations/{id}/reject` | `validations.reject` | Refus |

---

## Configuration du circuit

Le nombre de niveaux et leur ordre sont configurés dans [Admin — Niveaux de validation](10_admin_niveaux_validation.md).
Le circuit peut avoir 1, 2, 3 niveaux ou plus selon les besoins de l'organisation.
