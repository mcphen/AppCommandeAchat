Fonctionnalités cœur de métier

1\. ~~Gestion budgétaire~~

~~C'est le manque le plus criant. Aujourd'hui on approuve des commandes sans savoir si le budget est disponible.~~



~~Budget annuel/mensuel par boutique ou par catégorie~~

~~Consommation en temps réel (engagé vs disponible)~~

~~Blocage automatique si dépassement, ou alerte au validateur~~

~~Tableau de bord budgétaire pour l'admin~~



~~2. Catégories \& lignes de commande~~

~~Aujourd'hui une commande = un titre + un montant global. C'est trop limité.~~



~~Catégories (fournitures, matériel, services, IT...)~~

~~Lignes de détail (quantité × prix unitaire = total)~~

Circuit de validation différent selon la catégorie ou le montant (ex: < 100k XOF = 1 niveau, > 500k = 3 niveaux)

~~3. Fournisseurs~~

~~Référentiel de fournisseurs (nom, contact, RIB, délai, évaluation)~~

~~Lier une commande à un fournisseur~~

~~Historique des achats par fournisseur~~

~~Alerte si fournisseur non homologué~~

4\. ~~Bon de commande officiel → Bon de réception~~

~~Le circuit ne s'arrête pas à l'approbation.~~



~~Générer un bon de commande numéroté et signé (PDF)~~

~~Suivi livraison : commande → reçue partiellement → reçue entièrement~~

~~Qui a réceptionné, quand, avec quelles réserves~~





Architecture \& technique

5\. API REST complète

Pour permettre une app mobile, des intégrations ERP, ou un front découplé.



Endpoints JSON sécurisés (Sanctum tokens)

Documentation OpenAPI/Swagger auto-générée



6\. Application mobile (PWA ou native)

Les validateurs ont besoin de valider depuis leur téléphone.



Vue des commandes en attente

Approuver / Rejeter avec commentaire en 2 clics

Notifications push



7\. Intégrations comptables

Export vers un logiciel de comptabilité (Sage, QuickBooks...)

Génération d'écritures comptables à l'approbation finale

Rapprochement facture / bon de commande

Expérience utilisateur \& pilotage



~~8. Tableau de bord analytique avancé~~

~~Délai moyen de validation par niveau, par validateur~~

~~Commandes bloquées (en attente depuis X jours) → alertes~~

~~Top 5 fournisseurs, Top 5 catégories~~

~~Courbe de dépenses mensuelle par boutique~~

~~Taux de refus par demandeur (pour identifier les mauvaises pratiques)~~



9\. ~~Délégation de validation~~

~~Un validateur peut être absent. Aujourd'hui les commandes sont bloquées.~~



~~Déléguer ses droits à un autre utilisateur pour une période donnée~~

~~Validation par intérim traçée dans l'audit~~



10\. ~~Commentaires \& négociation~~

~~Discussion entre demandeur et validateur sur une commande avant décision~~

~~Demande de modification sans rejeter formellement~~

~~Pièces jointes ajoutées par le validateur (devis comparatif, note...)~~



Ce que je mettrais en v2 en premier



Priorité 1 (indispensable)  → Catégories + lignes de détail + budget

Priorité 2 (différenciateur) → Fournisseurs + bon de réception + délégation

Priorité 3 (scale)          → API REST + mobile + intégration comptable

La gestion budgétaire seule justifie une v2 : sans elle, l'approbation n'a pas de garde-fou financier réel — on valide des montants sans savoir si l'argent existe.

