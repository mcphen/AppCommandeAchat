# Réceptions & Livraisons

## Vue d'ensemble

Le module de réception permet de tracer la livraison physique des marchandises commandées. Il supporte les réceptions **partielles** (plusieurs livraisons successives) et **complètes** (tout reçu en une fois). Chaque réception peut être associée à une **facture fournisseur** pour assurer le rapprochement BC / facture.

**Contrôleur** : `app/Http/Controllers/ReceptionController.php`

---

## Prérequis

Pour qu'une commande soit réceptionnable, elle doit :
1. Avoir le statut `approved`
2. Avoir été confirmée par l'admin avec "Confirmer la commande" → `delivery_status = ordered`

---

## Statuts de livraison

| Statut | Valeur DB | Description |
|--------|-----------|-------------|
| — | `null` | Pas encore confirmée comme commandée |
| Commandée | `ordered` | Admin a confirmé la commande auprès du fournisseur |
| Partiellement reçue | `partially_received` | Au moins une livraison enregistrée, pas encore complète |
| Entièrement reçue | `received` | Toutes les lignes entièrement reçues |

---

## Saisie d'une réception

**Route** : `POST /purchase-orders/{id}/receptions`

### Champs de la réception
- **Date de réception** *(obligatoire)* : date à laquelle les articles ont été reçus
- **Notes** *(optionnel, max 1 000 caractères)* : observations, anomalies, commentaires
- **Lignes de réception** : pour chaque ligne de commande, quantité reçue lors de cette livraison
- **N° de facture fournisseur** *(optionnel)* : peut être renseigné immédiatement ou après coup
- **Date de la facture** *(optionnel)*
- **Montant facturé** *(optionnel)* : permet de détecter un écart avec le montant du BC

### Logique de mise à jour du statut

Après chaque réception, le système compare les quantités :

```
Pour chaque ligne :
    total_reçu = somme de toutes les réceptions sur cette ligne

Si TOUTES les lignes : total_reçu >= quantité commandée
    → delivery_status = "received", type = "complete"
    → fully_received_at = maintenant

Sinon :
    → delivery_status = "partially_received", type = "partial"
```

---

## Rapprochement facture / bon de commande

Les informations de facture peuvent être saisies lors de la réception ou rattachées ultérieurement.

**Route de mise à jour** : `PATCH /purchase-orders/{id}/receptions/{reception}/invoice`

### Statuts de rapprochement (calculés côté front)

| Statut | Condition |
|--------|-----------|
| **Facture rapprochée** ✅ | Toutes les réceptions ont un N° de facture ET `Σ montants facturés ≈ montant BC` |
| **Écart de montant** ⚠️ | Toutes les réceptions facturées mais `Σ factures ≠ montant BC` (écart > 1 XOF) |
| **Partiellement facturé** | Certaines réceptions ont une facture, d'autres non |
| **Sans facture** | Aucune réception n'a de facture attachée |

### Affichage dans la page commande

- Un badge de statut de rapprochement est affiché dans la section "Réceptions"
- Chaque réception affiche son numéro de facture, sa date et son montant
- Un bouton "Rattacher une facture" permet de saisir les infos directement depuis la page commande sans recharger la page

### Affichage dans la page Comptabilité (admin)

La colonne "N° pièce" affiche le numéro de BC ainsi que les numéros de factures associées lorsqu'elles sont disponibles.

---

## Tableau de bord des réceptions

**Route** : `GET /receptions`  
**Pagination** : 15 commandes par page

Affiche uniquement les commandes dont le `delivery_status` est `ordered` ou `partially_received`.

### Statistiques en en-tête

- Nombre de commandes **commandées** (en attente de première livraison)
- Nombre de commandes **partiellement reçues**
- Nombre de commandes **entièrement reçues** (historique)

### Filtres disponibles

- Statut de livraison (`ordered` / `partially_received`)
- Boutique
- Recherche textuelle (titre, numéro de commande)

---

## Modèle de données

### PurchaseOrderReception
| Champ | Type | Description |
|-------|------|-------------|
| `purchase_order_id` | FK | Commande concernée |
| `received_by` | FK (users) | Utilisateur ayant saisi la réception |
| `received_at` | date | Date de réception physique |
| `type` | enum | `partial` ou `complete` |
| `notes` | text | Observations |
| `invoice_number` | string(100) | N° de facture fournisseur |
| `invoice_date` | date | Date de la facture |
| `invoice_amount` | decimal(15,2) | Montant facturé (pour rapprochement) |

### PurchaseOrderReceptionLine
| Champ | Type | Description |
|-------|------|-------------|
| `reception_id` | FK | Réception parente |
| `purchase_order_line_id` | FK | Ligne de commande |
| `quantity_received` | decimal | Quantité reçue lors de cette réception |

---

## Droits d'accès

| Action | Admin | Demandeur |
|--------|:-----:|:---------:|
| Voir le tableau des réceptions | ✅ (toutes) | ✅ (ses commandes) |
| Saisir une réception | ✅ | ✅ (ses commandes) |
| Rattacher / modifier une facture | ✅ | ✅ (ses commandes) |

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/receptions` | `receptions.index` | Tableau de bord livraisons |
| POST | `/purchase-orders/{id}/receptions` | `purchase-orders.receptions.store` | Enregistrement réception |
| PATCH | `/purchase-orders/{id}/receptions/{reception}/invoice` | `purchase-orders.receptions.invoice` | Rattacher / corriger une facture |
