# Commandes d'achat

## Vue d'ensemble

Module central de l'application. Permet aux demandeurs de créer des bons de commande (BC), de les enrichir avec des lignes d'articles, de les soumettre au circuit de validation, et de les suivre jusqu'à la réception.

**Contrôleur** : `app/Http/Controllers/PurchaseOrderController.php`

---

## Cycle de vie d'une commande

```text
[Brouillon] ──submit──► [En attente] ──approbation finale──► [Approuvée]
     ▲                     │     ▲                                 │
     │               (révision) │                           markOrdered
     │                     ▼   (re-soumission)                     │
     │             [Révision demandée]                             ▼
     │                                                       [Commandée]
     │                     │ (refus)                               │
     └────────────────── [Refusée] ◄──────────────────     réception
                                                                   │
                                              ┌────────────────────┴──────────────┐
                                              ▼                                   ▼
                                  [Partiellement reçue]              [Entièrement reçue]
```

---

## Statuts

| Statut | Valeur DB | Qui peut agir |
|--------|-----------|---------------|
| Brouillon | `draft` | Demandeur (créateur) |
| En attente | `pending` | Validateurs selon le niveau |
| Révision demandée | `needs_revision` | Demandeur (modification + re-soumission) |
| Approuvée | `approved` | Admin (markOrdered) |
| Refusée | `rejected` | Demandeur (re-édition possible) |

> Le statut `needs_revision` est déclenché par un validateur via le module [Commentaires & négociation](17_commentaires_negociation.md). La commande n'est pas rejetée — elle est suspendue en attente d'une correction du demandeur.

## Statuts de livraison

| Statut | Valeur DB | Déclencheur |
|--------|-----------|-------------|
| — | `null` | Commande pas encore confirmée |
| Commandée | `ordered` | Admin → "Confirmer la commande" |
| Partiellement reçue | `partially_received` | Réception partielle enregistrée |
| Entièrement reçue | `received` | Réception complète enregistrée |

---

## Création d'une commande

**Qui** : Demandeur, Admin  
**Route** : `GET /purchase-orders/create`

### Champs disponibles
- **Titre** *(obligatoire)* : intitulé de la commande
- **Description** : contexte / justification
- **Fournisseur** : choix parmi les fournisseurs approuvés
- **Pièces jointes** : fichiers joints (PDF, images, etc.)

### Lignes de commande
Chaque ligne contient :
- **Article** : sélection dans le catalogue
- **Fournisseur** de la ligne (peut différer du fournisseur principal)
- **Quantité**
- **Prix unitaire** (pré-rempli depuis le catalogue, modifiable)
- **Note** (optionnel)

Le montant total est **recalculé automatiquement** : `Σ (quantité × prix unitaire)`.

### Pièces jointes
Stockées dans le disque privé sous `/attachments/{order_id}/`.

---

## Soumission pour validation

**Qui** : Demandeur (créateur), Admin  
**Route** : `POST /purchase-orders/{id}/submit`

**Règles** :

- Le statut doit être `draft`, `rejected` ou `needs_revision`
- Au moins un niveau de validation doit exister

**Comportement selon le statut d'origine** :

| Statut avant soumission | Niveau de départ | Raison |
| ----------------------- | ---------------- | ------ |
| `draft` / `rejected` | Niveau 1 | Nouveau circuit complet |
| `needs_revision` | Niveau ayant demandé la révision | Reprise au point de suspension |

Les validateurs du niveau cible reçoivent une notification.

---

## Confirmation de commande (markOrdered)

**Qui** : Admin uniquement  
**Route** : `POST /purchase-orders/{id}/mark-ordered`

**Règles** :
- La commande doit être `approved`
- Génère le numéro officiel au format `BC-{année}-{séquence sur 5 chiffres}` (ex : `BC-2026-00042`)
- Passe le `delivery_status` à `ordered`

---

## Edition

**Qui** : Créateur de la commande, Admin  
**Règle** : uniquement si statut est `draft`, `rejected` ou `needs_revision`

---

## Suppression

**Qui** : Créateur, Admin  
**Règle** : uniquement si statut est `draft` ou `rejected`. Les fichiers joints sont supprimés.

---

## Liste et filtres

**Route** : `GET /purchase-orders`  
**Pagination** : 10 commandes par page

Filtres disponibles :
- Statut (draft / pending / approved / rejected)
- Boutique (admin uniquement)
- Niveau de validation en cours
- Plage de dates
- Plage de montants
- Recherche textuelle (titre, numéro de commande)

---

## Export

**Route** : `GET /purchase-orders/export/{format}`  
**Formats** : `csv`, `excel`, `pdf`

Les exports respectent les mêmes filtres que la liste. Chaque ligne inclut :
- Numéro de commande, titre, boutique, demandeur
- Montant, statut, date de soumission
- Détail du circuit de validation (qui a approuvé à chaque niveau)

---

## Téléchargement PDF individuel

**Route** : `GET /purchase-orders/{id}/pdf`  
**Accessible** : tous les rôles authentifiés

Génère un PDF du bon de commande avec les lignes, le fournisseur et les informations de validation.

---

## Routes complètes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/purchase-orders` | `purchase-orders.index` | Liste |
| GET | `/purchase-orders/create` | `purchase-orders.create` | Formulaire création |
| POST | `/purchase-orders` | `purchase-orders.store` | Enregistrement |
| GET | `/purchase-orders/{id}` | `purchase-orders.show` | Détail |
| GET | `/purchase-orders/{id}/edit` | `purchase-orders.edit` | Formulaire édition |
| PUT | `/purchase-orders/{id}` | `purchase-orders.update` | Mise à jour |
| DELETE | `/purchase-orders/{id}` | `purchase-orders.destroy` | Suppression |
| POST | `/purchase-orders/{id}/submit` | `purchase-orders.submit` | Soumission |
| POST | `/purchase-orders/{id}/mark-ordered` | `purchase-orders.mark-ordered` | Confirmation |
| GET | `/purchase-orders/{id}/pdf` | `purchase-orders.pdf` | PDF |
| GET | `/purchase-orders/export/{format}` | `purchase-orders.export` | Export |
| POST | `/purchase-orders/{id}/comments` | `order-comments.store` | Poster un commentaire |
| GET | `/purchase-orders/{id}/comments/{comment}/download` | `order-comments.download` | Télécharger la pièce jointe |
| DELETE | `/purchase-orders/{id}/comments/{comment}` | `order-comments.destroy` | Supprimer un commentaire |
