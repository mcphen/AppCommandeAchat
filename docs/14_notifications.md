# Notifications

## Vue d'ensemble

L'application envoie des notifications automatiques aux utilisateurs concernés à chaque étape du cycle de vie d'une commande. Chaque notification est envoyée sur **deux canaux simultanément** : email et in-app (base de données).

**Répertoire** : `app/Notifications/`

---

## Canaux

| Canal | Description |
| ----- | ----------- |
| **Email** | Envoyé à l'adresse email de l'utilisateur |
| **Base de données** | Notification in-app, visible dans la cloche de la barre latérale |

---

## Liste des notifications

### 1. `OrderSubmittedNotification` — Nouvelle commande à valider

| Attribut | Valeur |
| -------- | ------ |
| **Déclencheur** | Soumission d'une commande (`PurchaseOrderController::submit`) |
| **Destinataires** | Tous les validateurs du **niveau 1** |
| **Objet email** | "Nouvelle commande à valider — {titre}" |
| **Lien d'action** | Page de validation (`validations.show`) |

**Contenu** : informe les validateurs L1 qu'une nouvelle commande est en attente de leur approbation. Inclut le montant et le nom du demandeur.

---

### 2. `OrderApprovedAtLevelNotification` — Commande transmise au niveau suivant

| Attribut | Valeur |
| -------- | ------ |
| **Déclencheur** | Approbation par un validateur intermédiaire (`ValidationController::approve`) |
| **Destinataires** | Tous les validateurs du **niveau suivant** |
| **Objet email** | "Commande en attente de votre validation — {titre}" |
| **Lien d'action** | Page de validation (`validations.show`) |

**Contenu** : informe les validateurs du niveau N+1 que la commande vient d'être approuvée au niveau N et nécessite maintenant leur décision.

> Cette notification n'est **pas envoyée** si le niveau approuvé était le dernier.

---

### 3. `OrderFinallyApprovedNotification` — Commande définitivement approuvée

| Attribut | Valeur |
| -------- | ------ |
| **Déclencheur** | Approbation par le validateur du **dernier niveau** |
| **Destinataires** | Le **créateur** de la commande (demandeur) |
| **Objet email** | "Votre commande a été approuvée — {titre}" |
| **Lien d'action** | Détail de la commande (`purchase-orders.show`) |

**Contenu** : bonne nouvelle — la commande a passé tous les niveaux de validation. Inclut le montant et incite l'utilisateur à consulter le détail.

---

### 4. `OrderRejectedNotification` — Commande refusée

| Attribut | Valeur |
| -------- | ------ |
| **Déclencheur** | Refus par n'importe quel validateur (`ValidationController::reject`) |
| **Destinataires** | Le **créateur** de la commande (demandeur) |
| **Objet email** | "Votre commande a été refusée — {titre}" |
| **Lien d'action** | Formulaire d'édition (`purchase-orders.edit`) |

**Contenu** : informe le demandeur du refus, indique le niveau auquel le refus a eu lieu, et affiche le **commentaire de refus** obligatoire. Le lien d'action pointe directement vers l'édition pour permettre la correction et la resoumission.

---

### 5. `DelegationReceivedNotification` — Délégation de validation reçue

| Attribut | Valeur |
| -------- | ------ |
| **Déclencheur** | Création d'une délégation (`DelegationController::store`) |
| **Destinataires** | Le **délégataire** (utilisateur recevant les droits) |
| **Objet email** | "Délégation de validation reçue" |
| **Lien d'action** | Page des délégations (`delegations.index`) |

**Contenu** : informe l'utilisateur qu'il a reçu temporairement les droits de validation d'un niveau, avec les dates de début et de fin, et le motif fourni par le délégant.

---

### 6. `OrderRevisionRequestedNotification` — Révision demandée sur une commande

| Attribut | Valeur |
| -------- | ------ |
| **Déclencheur** | Envoi d'un message de type `revision_request` (`OrderCommentController::store`) |
| **Destinataires** | Le **créateur** de la commande (demandeur) |
| **Objet email** | "Révision demandée sur votre commande — {titre}" |
| **Lien d'action** | Détail de la commande (`purchase-orders.show`) |

**Contenu** : informe le demandeur qu'un validateur souhaite une modification avant de prendre sa décision. Inclut le message du validateur. Le statut de la commande passe à `needs_revision` — le demandeur peut modifier et re-soumettre.

---

### 7. `OrderCommentAddedNotification` — Nouveau commentaire sur une commande

| Attribut | Valeur |
| -------- | ------ |
| **Déclencheur** | Envoi d'un commentaire simple (`OrderCommentController::store`) |
| **Destinataires** | Les **autres parties** : si l'auteur est le demandeur → validateurs du niveau courant ; si l'auteur est un validateur → créateur de la commande |
| **Objet email** | "Nouveau message sur la commande — {titre}" |
| **Lien d'action** | Détail de la commande (`purchase-orders.show`) |

**Contenu** : notifie les autres participants de la discussion qu'un message a été posté. Inclut un extrait du message et un lien direct vers la commande.

---

## Récapitulatif du flux de notifications

```text
Soumission commande
       │
       ▼
Validateurs L1 ← OrderSubmittedNotification

       │ (approbation L1)
       ▼
Validateurs L2 ← OrderApprovedAtLevelNotification

       │ (approbation L2 = dernier niveau)
       ▼
Demandeur ← OrderFinallyApprovedNotification

      OU

       │ (refus à n'importe quel niveau)
       ▼
Demandeur ← OrderRejectedNotification

      OU

       │ (demande de révision par un validateur)
       ▼
Demandeur ← OrderRevisionRequestedNotification

       │ (commentaire simple)
       ▼
Autres parties ← OrderCommentAddedNotification

Création délégation
       │
       ▼
Délégataire ← DelegationReceivedNotification
```

---

## Panneau de notifications in-app

Un **compteur de notifications non lues** est affiché dans l'en-tête de la barre latérale (icône cloche). Cliquer dessus ouvre un panneau listant les notifications récentes.

### Polling (mise à jour temps réel)

**Route** : `GET /notifications/poll`

L'interface interroge régulièrement cette route pour mettre à jour le compteur sans recharger la page.

### Marquer comme lues

| Action | Route | Nom |
| ------ | ----- | --- |
| Marquer toutes comme lues | `POST /notifications/read-all` | `notifications.read-all` |
| Marquer une comme lue | `GET /notifications/{id}/read` | `notifications.read` |

---

## Couleurs des notifications

| Type | Couleur | Usage |
| ---- | ------- | ----- |
| `order_submitted` | Bleu | Nouvelle commande à valider |
| `order_approved_at_level` | Amber | Commande transmise |
| `order_finally_approved` | Emerald | Approbation finale |
| `order_rejected` | Rouge | Refus |
| `order_revision_requested` | Indigo | Révision demandée |
| `order_comment_added` | Slate | Commentaire simple |
| `delegation_received` | Violet | Délégation reçue |
