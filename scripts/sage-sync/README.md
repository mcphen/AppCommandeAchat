# Intégration Sage100 → application Laravel (webhook + script de synchro Windows)

Ce dossier documente le pattern complet utilisé pour synchroniser des documents
Sage100 (ici : commandes fournisseur) vers une application Laravel, via un
webhook + un script PowerShell planifié sur le serveur Windows qui héberge
Sage100. Sert de guide réutilisable pour un autre projet/client.

## Vue d'ensemble de l'architecture

```
Sage100 (SQL Server, sur le serveur Windows du client)
        │
        │  1. lecture seule (F_DOCENTETE / F_DOCLIGNE / F_COMPTET / F_COLLABORATEUR)
        ▼
Sync-PurchaseOrders.ps1 (Planificateur de tâches Windows, toutes les 10 min)
        │
        │  2. POST JSON + token API (X-API-Key)
        ▼
API Laravel : POST /api/sage/purchase-orders
        │
        │  3. upsert (idempotent par référence Sage), création auto
        │     fournisseurs/articles/demandeurs manquants
        ▼
Base de données de l'application (visible dans l'UI, circuit de validation)
```

Principe directeur : **Sage100 reste la source de vérité pour la création**.
L'app ne fait que recevoir, afficher et faire valider — pas de création
manuelle côté app une fois l'intégration en place.

---

## Partie 1 — Checklist réutilisable pour un nouveau projet

### Étape 0 — Trouver le bon type de document dans Sage100

Sage100 stocke tous les documents commerciaux (devis, commandes, BL, factures,
côté vente ET achat) dans les mêmes tables `F_DOCENTETE`/`F_DOCLIGNE`, distingués
par deux colonnes : `DO_Domaine` (0 = Ventes, 1 = Achats, 2 = Stock...) et
`DO_Type` (Devis/Commande/BL/Facture, numéroté différemment par domaine).

**Ne jamais deviner** le couple Domaine/Type — toujours vérifier avec le script
`Discover-SageSchema.ps1` (fourni dans ce dossier) :
1. Se connecter à la base Sage100 (voir étape 1 ci-dessous pour les identifiants)
2. Lancer une requête `GROUP BY DO_Domaine, DO_Type` avec échantillon de pièces
3. Croiser avec `F_COMPTET.CT_Type` (0 = client, 1 = fournisseur) via jointure
   sur `DO_Tiers = CT_Num` pour confirmer sans ambiguïté
4. Les préfixes de pièces aident à deviner (`BC` = Bon de Commande, `F` devant
   = Fournisseur dans certaines instances) mais **ne sont pas fiables seuls** —
   toujours confirmer avec `CT_Type`.

### Étape 1 — Trouver le serveur/instance/base SQL Server

- Un serveur Windows peut héberger **plusieurs instances** SQL Server
  nommées (`localhost\SAGE100`, `localhost\SQL2019`...) → lister avec
  `Get-Service | Where-Object { $_.DisplayName -like "*SQL*" }`
- Chaque instance peut contenir **plusieurs bases** (une par société/exercice
  comptable) → lister avec `SELECT name, state_desc FROM sys.databases`
- Le nom "évident" donné par le client n'est pas toujours le bon (dans ce
  projet, `CONSTRUCSEN2024` existait bien, mais il fallait bien vérifier
  qu'elle contenait les vraies tables `F_DOCENTETE` et pas juste une base de
  connexion/métadonnées d'un module Sage100cloud à part)
- **Authentification** : privilégier l'authentification Windows intégrée du
  compte qui exécute le script plutôt que le compte `sa` (souvent désactivé
  ou avec un mot de passe inconnu/oublié). Vérifier les droits avec :
  ```sql
  SELECT sp.name, IS_SRVROLEMEMBER('sysadmin', sp.name) AS EstSysadmin
  FROM sys.server_principals sp WHERE sp.type IN ('S','U','G')
  ```

### Étape 2 — Construire le contrat JSON du webhook

Lister les champs Sage nécessaires (identifiant pièce, date, tiers, montants,
lignes) et les champs "bonus" pour enrichir l'app (nom/adresse/tel/email du
tiers via `F_COMPTET`, demandeur réel via `F_COLLABORATEUR`/`CO_No`). Garder
tous les champs bonus **optionnels** côté validation Laravel (`nullable`),
avec un fallback sensé si absents (ex : nom du fournisseur = son code brut si
`F_COMPTET` ne répond rien).

### Étape 3 — Écrire le contrôleur webhook Laravel

Points non négociables observés sur ce projet :

- **Idempotence obligatoire** : contrainte `unique` en base sur la référence
  externe (ex: `sage_reference`), et upsert (`firstOrCreate`/update) au lieu
  d'un simple `create()`. Sans ça, rejouer le script = doublons.
- **Auth par token statique** (`X-API-Key` comparé via `hash_equals`), pas
  besoin d'OAuth pour un système tiers qui ne gère pas de session — voir
  `App\Http\Middleware\EnsureSageApiToken`.
- **Création automatique des entités liées manquantes** (fournisseurs,
  articles, demandeurs) en mode "stub à vérifier" plutôt que de rejeter le
  webhook — sinon une commande entière bloque pour un seul code inconnu.
  Voir `findOrCreateFournisseur`/`findOrCreateArticle`/`findOrCreateDemandeur`
  dans `App\Http\Controllers\Api\SageWebhookController`.
- **Auto-correction des stubs tant que non validés** : si un stub a été créé
  avec un nom générique faute de donnée dispo, et qu'une meilleure donnée
  arrive plus tard, la mettre à jour — mais seulement tant qu'un admin ne l'a
  pas encore validé/complété manuellement (`is_approved = false`).
- **Traiter toute la commande dans un `try/catch`** au niveau PHP aussi : une
  ligne avec un champ NULL ne doit jamais faire planter tout le lot.
- **Logger chaque appel** (succès/erreur/payload) dans une table dédiée
  (`sage_webhook_logs`) pour pouvoir diagnostiquer sans devoir fouiller les
  logs serveur bruts.

### Étape 4 — Écrire le script PowerShell de synchro

Bugs rencontrés et déjà corrigés dans `Sync-PurchaseOrders.ps1` — à ne pas
refaire sur un nouveau projet :

1. **`.Trim()` sur une valeur SQL potentiellement `NULL`/`DBNull` plante le
   script.** Toujours passer par un helper `Get-SafeTrim`/`Get-SafeDouble` qui
   gère `DBNull` proprement (les vraies données de prod sont bien plus sales
   qu'un jeu de données de démo).
2. **Ne jamais faire une requête SQL paramétrée par pièce dans une boucle**
   pour récupérer les lignes détail — dans ce projet, un souci de binding de
   paramètre (jamais élucidé avec certitude, probablement lié au type
   NVARCHAR/VARCHAR implicite) faisait échouer silencieusement certaines
   requêtes alors que la même requête en littéral fonctionnait. Solution
   robuste : **une seule requête qui ramène TOUTES les lignes**, puis on les
   regroupe par pièce côté PowerShell (comparaison .NET pure, fiable).
3. **PowerShell 5.1 corrompt les caractères accentués** si on passe une
   chaîne texte brute à `Invoke-RestMethod -Body`. Toujours convertir en
   octets UTF-8 explicitement :
   ```powershell
   $utf8Bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
   Invoke-RestMethod ... -ContentType "application/json; charset=utf-8" -Body $utf8Bytes
   ```
4. **PowerShell 5.1 / .NET Framework négocie parfois du TLS 1.0 par défaut**,
   que le serveur cible refuse silencieusement — la requête échoue avec une
   erreur de connexion générique ("La connexion a été interrompue de manière
   inattendue"), **jamais** une vraie erreur HTTP, même après plusieurs retry.
   Piège sournois : un `Invoke-WebRequest` sur la page d'accueil du site
   fonctionne (servie différemment), ce qui fait croire à tort que le réseau va
   bien. Forcer TLS 1.2 en toute première ligne du script, avant le moindre
   appel HTTP :

   ```powershell
   [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
   ```

5. **Le "watermark" (curseur de synchro incrémentale) ne doit jamais dépasser
   la date de la première vraie erreur du lot**, sinon les pièces en échec ne
   sont plus jamais retentées (le filtre `cbModification > @watermark` les
   saute pour toujours). Voir la logique `$firstErrorModification` en fin de
   script.
6. **Retry avec backoff sur les erreurns de connexion** (pas sur les vraies
   erreurs HTTP 4xx qui ne se régleront pas en retentant), + **pause entre
   chaque commande** (300ms) pour ne pas saturer PHP-FPM sur un gros volume
   (1400+ commandes d'un coup au premier import).
7. **Créer une migration défensive** (`Schema::hasColumn(...)` avant d'ajouter
   une colonne) si la base de prod a pu dériver du schéma attendu par les
   migrations du dépôt (arrive si la prod a été provisionnée à un moment
   différent de l'historique git).

### Étape 5 — Sécuriser le token et planifier

- **Ne jamais mettre le token API en argument visible d'une tâche planifiée**
  (n'importe quel admin peut le lire dans les propriétés de la tâche). Passer
  par un fichier de config JSON local avec permissions NTFS restreintes
  (`icacls ... /grant:r "SYSTEM:F" "Administrators:F"`), lu par un script
  wrapper (`Run-Sync.ps1`) — voir ce pattern dans ce dossier.
- **Ajouter le fichier de config réel et les fichiers d'état/logs au
  `.gitignore`** — jamais committer un token en clair.
- `New-ScheduledTaskTrigger -RepetitionDuration ([TimeSpan]::MaxValue)`
  **plante** (dépasse la limite XML du Planificateur) — utiliser
  `(New-TimeSpan -Days 3650)` à la place.
- Vérifier que le compte qui exécute la tâche (`SYSTEM` ou un compte de
  service dédié) a bien les droits SQL — ce n'est pas parce que ton compte
  interactif fonctionne que `SYSTEM` fonctionnera aussi.

### Étape 6 — Vérifier que l'automatisation tourne vraiment

```powershell
Get-ScheduledTaskInfo -TaskName "<nom-tache>" | Format-List   # LastRunTime, NextRunTime, LastTaskResult
Get-ScheduledTask -TaskName "<nom-tache>" | Select TaskName, State
Get-Content .\sync.log -Tail 30                                 # doit avancer tout seul, sans action manuelle
```
Historique détaillé : `taskschd.msc` → tâche → onglet **Historique**.

### Étape 7 — Ne pas spammer le circuit de validation avec de l'historique déjà réglé

Si on importe des documents anciens (ex : tout l'historique 2024), une bonne partie
est déjà **clôturée**/livrée dans Sage — inutile de demander à un validateur de les
approuver a posteriori. Deux colonnes Sage à exploiter :

- `F_DOCENTETE.DO_Cloture` (0/1) → si clôturé, importer directement en statut final
  de l'app (`approved`), sans passer par le circuit de validation ni notifier
  personne (voir `$isCloture` dans `SageWebhookController::upsertOrder`).
- `F_DOCLIGNE.DL_QteBL` (quantité livrée) comparée à `DL_Qte` (quantité commandée)
  → permet de déduire `delivery_status` (`received`/`partially_received`/`null`)
  automatiquement, sans attendre qu'un utilisateur confirme la réception à la main.

**Ne pas se contenter d'un badge de synthèse calculé** : persister aussi le détail
(`PurchaseOrderReception`/`PurchaseOrderReceptionLine`), sinon le badge global dit
"reçue" mais la barre de progression par ligne de l'UI reste à 0% (incohérence).
Champs Sage utiles : `DL_QteBL` (qté livrée), `DL_DateBL` (date), `DL_PieceBL`
(n° de bon de livraison). Marquer les réceptions créées par le sync avec un préfixe
reconnaissable dans `notes` (ex: `[Import Sage100]`) pour pouvoir les supprimer et
recréer proprement à chaque resynchronisation sans dupliquer ni toucher aux
réceptions saisies manuellement par un utilisateur.

### Étape 8 — Se méfier des codes articles génériques réutilisés

Sur ce projet, la majorité des `AR_Ref` ne sont pas des références catalogue
uniques : ce sont des **codes génériques de dépense** réutilisés par les achats
(ex: `CAISCHANT` = "caisse de chantier", utilisé 213 fois avec 68 désignations
réelles différentes). Si on ne garde le libellé Sage (`DL_Design`) que lors de la
création de l'article (`firstOrCreate`), l'app affiche pour toujours le tout premier
libellé vu, même quand la ligne concerne en réalité autre chose.

**Toujours dupliquer la désignation réelle sur la ligne de commande elle-même**
(`purchase_order_lines.note`), en plus de l'utiliser pour nommer l'article générique
à sa création — ne jamais compter sur le nom de l'article seul pour représenter le
contenu réel d'une ligne. Vérifier l'ampleur du phénomène avant de se fier au
matching par code seul :
```sql
SELECT AR_Ref, COUNT(DISTINCT DL_Design) AS NbDesignations, COUNT(*) AS NbLignes
FROM F_DOCLIGNE
WHERE DO_Domaine = 1 AND DO_Type = 12
GROUP BY AR_Ref
HAVING COUNT(DISTINCT DL_Design) > 1
ORDER BY NbDesignations DESC
```

---

## Partie 2 — Référence spécifique à ce projet (Construcsen / achats.construcsen.com)

| Élément | Valeur |
|---|---|
| Domaine Sage (commande fournisseur) | `DO_Domaine = 1`, `DO_Type = 12` (préfixe pièce `FBC` en démo, `BC` chez Construcsen) |
| Instance/base réelle | `localhost\SQL2019` / `CONSTRUCSEN2024` |
| Base de démo utilisée pour les tests | `localhost\SAGE100` / `bijou` (base "Bijouterie" livrée par défaut avec Sage100) |
| Endpoint webhook | `POST https://achats.construcsen.com/api/sage/purchase-orders` |
| Auth webhook | Header `X-API-Key`, valeur = `SAGE_API_TOKEN` (`.env` Laravel) |
| Tâche planifiée Windows | `Sync-Sage-PurchaseOrders`, toutes les 10 min |
| Fichiers | `Discover-SageSchema.ps1` (exploration), `Sync-PurchaseOrders.ps1` (synchro), `Run-Sync.ps1` (wrapper config), `sage-sync.config.json` (secrets, non commité) |

### Contrat JSON envoyé au webhook

```json
{
  "numero": "BC092",
  "date": "2024-01-12",
  "tiers": "401MAGUETTEDIOUCK",
  "tiers_nom": "...",
  "tiers_adresse": "...",
  "tiers_ville": "...",
  "tiers_telephone": "...",
  "tiers_email": "...",
  "tiers_siret": "...",
  "montant_ht": 600000,
  "montant_ttc": 600000,
  "projet_code": "CHANTIER-042",
  "lignes": [
    {
      "article": "LOC", "designation": "LOCATION BETONNIERE", "famille_article": "LOCATION",
      "quantite": 20, "prix_unitaire": 30000, "taux_tva": 18, "remise": 0, "unite": "JOUR"
    }
  ],
  "demandeur": { "code": "8", "nom": "GERARD AFANOU", "email": "" }
}
```

Champs ajoutés le 2026-07-31 : `projet_code` (code affaire/chantier), `lignes[].famille_article`
(`F_ARTICLE`), `lignes[].taux_tva` et `lignes[].remise` (déduits des montants HT/TTC déjà
calculés par Sage sur la ligne), `lignes[].unite`. Tous optionnels côté validation Laravel —
un champ resté désactivé remonte simplement à `null` dans le payload, sans erreur.

**Tous désactivés par défaut** (`-ProjectCodeColumn ""`, `-LineAmountHtColumn ""`,
`-LineAmountTtcColumn ""`, `-SaleUnitColumn ""`, `-ArticleFamilyColumn ""`) : les noms de
colonnes standards Sage100 supposés au départ (`DO_ProjetCode`, `DL_Taux1`, `DL_Remise01`,
`DL_UniteVente`, `AR_FamilleCode`) n'existaient pas chez Construcsen sous ces noms exacts
(incident constaté le 2026-07-31 — la synchro entière plantait à chaque fois, car la requête
SQL référençait des colonnes inexistantes pour TOUTES les commandes du lot). Après exploration
du vrai schéma via `Discover-SageSchema.ps1 -SqlServer "localhost\SQL2019" -Database
"CONSTRUCSEN2024"` (**attention à l'instance réelle `SQL2019`, pas `SAGE100`** qui est la base
de démo "Bijouterie"), voici ce qui existe réellement chez ce client :

| Donnée | Colonne standard supposée (fausse) | Vrai nom chez Construcsen | Config à ajouter |
|---|---|---|---|
| Code affaire/chantier | `DO_ProjetCode` | Pas de module Affaires (`F_AFFAIRE` absente). `DO_Ref` (`F_DOCENTETE`) contient déjà des libellés qui ressemblent à des chantiers (`"SOMONE/PLOMBERIE"`, `"SN HLM BAMBILOR"`) — confirmé le 2026-08-24 sur `BC4167` (`DO_Ref = "SOMONE/CAISSE 1"`) | `"ProjectCodeColumn": "DO_Ref"` (activé le 2026-08-24) |
| TVA + remise ligne | `DL_Taux1` / `DL_Remise01` | Pas de taux exploitable simplement (`DL_Taxe1`/`DL_Remise01REM_Valeur`+`REM_Type` ont une sémantique ambiguë). Sage calcule déjà `DL_MontantHT`/`DL_MontantTTC` par ligne : le script déduit lui-même le taux effectif à partir de ces deux montants | `"LineAmountHtColumn": "DL_MontantHT"`, `"LineAmountTtcColumn": "DL_MontantTTC"` |
| Famille article | `AR_FamilleCode` | `FA_CodeFamille` (`F_ARTICLE`) | `"ArticleFamilyColumn": "FA_CodeFamille"` |
| Unité de ligne | `DL_UniteVente` | Aucune colonne texte sur `F_DOCLIGNE` (seulement `AR_UniteVen`, un code numérique sur l'article renvoyant à une table de paramètres non explorée) | reste désactivé |

Pour appliquer ces valeurs, ajouter dans `sage-sync.config.json` (`Run-Sync.ps1` transmet
chaque clé présente ; celles absentes restent désactivées sans bloquer les autres champs) :

```json
"LineAmountHtColumn": "DL_MontantHT",
"LineAmountTtcColumn": "DL_MontantTTC",
"ArticleFamilyColumn": "FA_CodeFamille"
```

`ProjectCodeColumn` (`DO_Ref`) activé le 2026-08-24 après confirmation sur `BC4167`
(`DO_Ref = "SOMONE/CAISSE 1"`, cohérent avec le chantier attendu). Ajouter dans
`sage-sync.config.json` (fichier non commité, présent uniquement sur le serveur) :
```json
"ProjectCodeColumn": "DO_Ref"
```
Les BC déjà importés avant activation ne récupèrent pas le chantier rétroactivement
par le sync — utiliser `Upgrade-PurchaseOrder.ps1` / `Run-Upgrade.ps1` pour ceux-là
(cf. section dédiée ci-dessous).

### Rattrapage chantier (`Upgrade-PurchaseOrder.ps1`)

Le sync principal laisse `-ProjectCodeColumn ""` (chantier désactivé, cf. tableau
ci-dessus) tant que l'hypothèse `DO_Ref` n'est pas confirmée sur un plus grand
échantillon. `Upgrade-PurchaseOrder.ps1` sert de script de rattrapage ponctuel
(à lancer manuellement, pas planifié) une fois l'hypothèse validée :

1. Demande à l'app `GET /api/sage/purchase-orders/missing-project` la liste des
   BC déjà importés mais sans chantier (`project_id NULL`).
2. Lit `DO_Ref` côté Sage pour chacun (une seule requête sur `F_DOCENTETE`, pas
   de boucle pièce-par-piece — même raison qu'étape 4 point 2 ci-dessus).
3. Renvoie le chantier trouvé via `PATCH /api/sage/purchase-orders/{numero}/project`
   (endpoint dédié, ne repasse pas tout le payload webhook).

```powershell
.\Upgrade-PurchaseOrder.ps1 -SqlServer "localhost\SQL2019" -Database "CONSTRUCSEN2024" `
    -ApiBaseUrl "https://achats.construcsen.com" -ApiToken "xxxxx" -DryRun
```

Une fois `-ProjectCodeColumn "DO_Ref"` activé dans `Sync-PurchaseOrders.ps1`
(ou `sage-sync.config.json`), les nouveaux BC arrivent avec leur chantier dès
l'import et ce script de rattrapage n'est plus utile que pour l'historique déjà
importé avant l'activation.

### Fichiers Laravel côté app

- `routes/api.php` — route du webhook
- `app/Http/Middleware/EnsureSageApiToken.php` — vérif token
- `app/Http/Controllers/Api/SageWebhookController.php` — logique complète
- `app/Models/SageWebhookLog.php` — table de logs
- Colonnes ajoutées : `purchase_orders.sage_reference/source/order_date`,
  `fournisseurs.sage_code/siret`, `articles.sage_reference`,
  `users.sage_collaborateur_code`
