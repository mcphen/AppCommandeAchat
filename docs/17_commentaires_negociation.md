# Commentaires & Négociation

## Vue d'ensemble

Le module de discussion permet aux demandeurs et aux validateurs d'échanger des messages directement sur une commande, avant toute décision formelle. Un validateur peut demander une révision sans rejeter la commande, et le demandeur peut répondre, modifier et re-soumettre dans la foulée. Chaque échange est horodaté, tracé, et peut être accompagné d'une pièce jointe.

**Contrôleur** : `app/Http/Controllers/OrderCommentController.php`  
**Modèle** : `app/Models/OrderComment.php`  
**Composant Vue** : `resources/js/components/OrderDiscussion.vue`

---

## Problème résolu

Sans ce module, le validateur n'a que deux choix : approuver ou rejeter formellement. Un rejet pour un simple document manquant ou un montant à ajuster crée une rupture dans le circuit (statut `rejected`, créateur notifié, doit re-soumettre depuis zéro). La discussion permet de **suspendre** la décision le temps que le demandeur apporte les éléments manquants.

---

## Types de messages

| Type | Valeur DB | Qui peut l'envoyer | Effet sur la commande |
|------|-----------|--------------------|----------------------|
| Commentaire simple | `comment` | Demandeur, Validateur, Admin | Aucun |
| Demande de révision | `revision_request` | Validateur, Admin uniquement | Statut → `needs_revision` |

---

## Nouveau statut : `needs_revision`

Ajouté à l'enum de la colonne `status` de `purchase_orders`.

| Propriété | Valeur |
|-----------|--------|
| **Valeur DB** | `needs_revision` |
| **Label** | Révision demandée |
| **Couleur** | Indigo |
| **Qui peut agir** | Demandeur (créateur) : modification + re-soumission |

### Cycle complet avec révision

```
[En attente] ──── commentaire simple ────────────────► [En attente]
      │                                                      ▲
      │   demande de révision (validateur)                   │
      ▼                                                      │
[Révision demandée]                                          │
      │                                                      │
      │   demandeur modifie et re-soumet                     │
      └─────────────────────────────────────────────────────►┘
              (reprend au niveau qui a demandé la révision)
```

---

## Règles métier

| Règle | Détail |
|-------|--------|
| Qui peut commenter | Demandeur (sur ses propres commandes) + Validateurs + Admin |
| Statuts commentables | `pending`, `needs_revision` (et `draft` pour le demandeur) |
| Demande de révision | Uniquement si la commande est en statut `pending` |
| Pièce jointe | 1 fichier par message, max 10 Mo |
| Formats acceptés | PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG |
| Suppression | Auteur du message uniquement (ou admin) ; les demandes de révision ne peuvent pas être supprimées |
| Re-soumission | Le demandeur re-soumet depuis la page de détail — la commande reprend au **niveau exact** qui avait demandé la révision |

---

## Pièces jointes sur commentaires

Les fichiers sont stockés dans le disque privé sous `/comments/{order_id}/`.

Téléchargement via : `GET /purchase-orders/{id}/comments/{comment}/download`  
Accessible à : demandeur (sa commande), validateurs, admin.

---

## Notifications

| Événement | Notification | Destinataires |
|-----------|-------------|---------------|
| Demande de révision | `OrderRevisionRequestedNotification` | Créateur de la commande |
| Commentaire simple | `OrderCommentAddedNotification` | Autres parties (demandeur si l'auteur est validateur, ou validateurs du niveau courant si l'auteur est le demandeur) |

---

## Composant `OrderDiscussion.vue`

Composant réutilisable intégré dans deux pages :

| Page | Props |
|------|-------|
| `PurchaseOrders/Show.vue` | `canRequestRevision: false` |
| `Validations/Show.vue` | `canRequestRevision: order.status === 'pending'` |

### Comportement du formulaire

- Demandeur : formulaire simple (type fixé à `comment`)
- Validateur / Admin : sélecteur de type (Commentaire / Demande de révision)
- En mode "Demande de révision" : avertissement affiché, bouton en amber
- Commande approuvée ou refusée : formulaire remplacé par un message "discussion fermée"

---

## Schéma base de données

### Table `order_comments`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Clé primaire |
| `purchase_order_id` | FK → purchase_orders | Commande concernée |
| `user_id` | FK → users | Auteur du message |
| `type` | enum(`comment`, `revision_request`) | Type de message |
| `content` | text | Corps du message |
| `attachment_path` | string nullable | Chemin du fichier en disque privé |
| `attachment_name` | string nullable | Nom original du fichier |
| `attachment_size` | unsigned int nullable | Taille en octets |
| `created_at` / `updated_at` | timestamps | — |

---

## Routes

| Méthode | URL | Nom | Accès | Action |
|---------|-----|-----|-------|--------|
| POST | `/purchase-orders/{id}/comments` | `order-comments.store` | Tous rôles | Poster un message |
| GET | `/purchase-orders/{id}/comments/{comment}/download` | `order-comments.download` | Tous rôles | Télécharger la pièce jointe |
| DELETE | `/purchase-orders/{id}/comments/{comment}` | `order-comments.destroy` | Auteur / Admin | Supprimer un message |
