<#
.SYNOPSIS
    Explore la base SQL Server de Sage100 pour identifier les tables et le DO_Type
    correspondant aux bons de commande fournisseur, avant d'écrire le script de synchro final.

.DESCRIPTION
    A executer directement sur (ou depuis) le serveur Windows qui heberge / a acces a la base
    Sage100 SQL Server. Lecture seule : ce script ne modifie rien.

.PARAMETER SqlServer
    Nom ou IP de l'instance SQL Server (ex: "localhost\SAGE100" ou "192.168.1.10").

.PARAMETER Database
    Nom de la base Sage100 (ex: "SCN_ACHATS" ou similaire).

.PARAMETER SqlUser
    Utilisateur SQL. Si omis, utilise l'authentification Windows integree.

.PARAMETER SqlPassword
    Mot de passe SQL (si SqlUser est fourni).

.EXAMPLE
    .\Discover-SageSchema.ps1 -SqlServer "localhost\SAGE100" -Database "SCN_GESCOM"

.EXAMPLE
    .\Discover-SageSchema.ps1 -SqlServer "192.168.1.10" -Database "SCN_GESCOM" -SqlUser "sa" -SqlPassword "motdepasse"
#>

param(
    [Parameter(Mandatory = $true)]
    [string]$SqlServer,

    [Parameter(Mandatory = $true)]
    [string]$Database,

    [string]$SqlUser,
    [string]$SqlPassword
)

function New-Connection {
    if ($SqlUser) {
        $connString = "Server=$SqlServer;Database=$Database;User Id=$SqlUser;Password=$SqlPassword;TrustServerCertificate=True;"
    } else {
        $connString = "Server=$SqlServer;Database=$Database;Integrated Security=True;TrustServerCertificate=True;"
    }
    $conn = New-Object System.Data.SqlClient.SqlConnection($connString)
    $conn.Open()
    return $conn
}

function Invoke-Query([System.Data.SqlClient.SqlConnection]$Connection, [string]$Sql) {
    $cmd = New-Object System.Data.SqlClient.SqlCommand($Sql, $Connection)
    $cmd.CommandTimeout = 30
    $adapter = New-Object System.Data.SqlClient.SqlDataAdapter($cmd)
    $table = New-Object System.Data.DataTable
    [void]$adapter.Fill($table)
    return $table
}

Write-Host "=== Connexion a $SqlServer / $Database ===" -ForegroundColor Cyan
try {
    $conn = New-Connection
} catch {
    $innerMessage = $_.Exception.InnerException.Message
    if (-not $innerMessage) { $innerMessage = $_.Exception.Message }
    Write-Host "ECHEC DE CONNEXION : $innerMessage" -ForegroundColor Red
    Write-Host "Verifie le nom du serveur/instance, la base, l'utilisateur et le mot de passe." -ForegroundColor Yellow
    exit 1
}
Write-Host "Connecte." -ForegroundColor Green

Write-Host "`n=== 1. Tables candidates (F_DOC*, F_ARTICLE, F_COMPTET...) ===" -ForegroundColor Cyan
$tables = Invoke-Query $conn @"
SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME LIKE 'F_DOC%'
   OR TABLE_NAME LIKE 'F_ARTICLE%'
   OR TABLE_NAME LIKE 'F_COMPTET%'
   OR TABLE_NAME LIKE '%DOCTYPE%'
ORDER BY TABLE_NAME
"@
$tables | Format-Table -AutoSize

if (-not ($tables | Where-Object { $_.TABLE_NAME -eq 'F_DOCENTETE' })) {
    Write-Host "`nATTENTION : la table F_DOCENTETE n'existe pas sous ce nom exact." -ForegroundColor Yellow
    Write-Host "Regarde la liste ci-dessus pour trouver l'equivalent (prefixe different selon la version Sage)." -ForegroundColor Yellow
    $conn.Close()
    return
}

Write-Host "`n=== 2. Colonnes de F_DOCENTETE ===" -ForegroundColor Cyan
Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_DOCENTETE' ORDER BY ORDINAL_POSITION" |
    Format-Table -AutoSize

Write-Host "`n=== 3. Colonnes de F_DOCLIGNE ===" -ForegroundColor Cyan
Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_DOCLIGNE' ORDER BY ORDINAL_POSITION" |
    Format-Table -AutoSize

Write-Host "`n=== 4. Repartition des DO_Domaine + DO_Type (pour identifier 'commande fournisseur') ===" -ForegroundColor Cyan
Write-Host "Rappel Sage100 : DO_Domaine distingue Ventes (0) / Achats (1) / Stock (2)." -ForegroundColor DarkGray
Invoke-Query $conn @"
SELECT DO_Domaine, DO_Type, COUNT(*) AS Nombre, MIN(DO_Piece) AS PremierePiece, MAX(DO_Piece) AS DernierePiece, MIN(DO_Date) AS PremiereDate, MAX(DO_Date) AS DerniereDate
FROM F_DOCENTETE
GROUP BY DO_Domaine, DO_Type
ORDER BY DO_Domaine, DO_Type
"@ | Format-Table -AutoSize

Write-Host "`n=== 5. Echantillon de 5 lignes recentes par DO_Domaine + DO_Type ===" -ForegroundColor Cyan
$types = Invoke-Query $conn "SELECT DISTINCT DO_Domaine, DO_Type FROM F_DOCENTETE ORDER BY DO_Domaine, DO_Type"
foreach ($row in $types) {
    $domaine = $row.DO_Domaine
    $type = $row.DO_Type
    Write-Host "`n--- DO_Domaine = $domaine / DO_Type = $type ---" -ForegroundColor Magenta
    Invoke-Query $conn @"
SELECT TOP 5 DO_Piece, DO_Date, DO_Tiers, DO_Ref, DO_Domaine, DO_Type
FROM F_DOCENTETE
WHERE DO_Domaine = $domaine AND DO_Type = $type
ORDER BY DO_Date DESC
"@ | Format-Table -AutoSize
}

Write-Host "`n=== 6. Table des tiers (fournisseurs) : F_COMPTET, colonnes cle ===" -ForegroundColor Cyan
if (Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'F_COMPTET'" | Select-Object -First 1) {
    Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_COMPTET' ORDER BY ORDINAL_POSITION" |
        Format-Table -AutoSize
    Invoke-Query $conn "SELECT TOP 5 CT_Num, CT_Intitule, CT_Type FROM F_COMPTET ORDER BY CT_Num" | Format-Table -AutoSize

    Write-Host "`n--- 6bis. CT_Type des tiers par DO_Domaine/DO_Type (pour trancher Client vs Fournisseur) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT d.DO_Domaine, d.DO_Type, ct.CT_Type, COUNT(*) AS Nombre, MIN(ct.CT_Intitule) AS ExempleTiers
FROM F_DOCENTETE d
LEFT JOIN F_COMPTET ct ON ct.CT_Num = d.DO_Tiers
GROUP BY d.DO_Domaine, d.DO_Type, ct.CT_Type
ORDER BY d.DO_Domaine, d.DO_Type
"@ | Format-Table -AutoSize
}

Write-Host "`n=== 7. Table des articles : F_ARTICLE, colonnes cle ===" -ForegroundColor Cyan
if (Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'F_ARTICLE'" | Select-Object -First 1) {
    Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_ARTICLE' ORDER BY ORDINAL_POSITION" |
        Format-Table -AutoSize
    Invoke-Query $conn "SELECT TOP 5 AR_Ref, AR_Design FROM F_ARTICLE ORDER BY AR_Ref" | Format-Table -AutoSize
}

Write-Host "`n=== 8. Colonnes 'code affaire/chantier' candidates sur F_DOCENTETE ===" -ForegroundColor Cyan
Write-Host "A verifier avant d'activer -ProjectCodeColumn dans Sync-PurchaseOrders.ps1." -ForegroundColor DarkGray
Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_DOCENTETE' AND (COLUMN_NAME LIKE '%Projet%' OR COLUMN_NAME LIKE '%Affaire%')" |
    Format-Table -AutoSize
if (Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'F_AFFAIRE'" | Select-Object -First 1) {
    Write-Host "Table F_AFFAIRE presente (module Affaires actif) :" -ForegroundColor Green
    Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_AFFAIRE' ORDER BY ORDINAL_POSITION" |
        Format-Table -AutoSize
} else {
    Write-Host "Table F_AFFAIRE absente : le client n'a probablement pas le module Affaires." -ForegroundColor Yellow
}

Write-Host "`n=== 9. Colonnes TVA/remise/unite/famille utilisees par Sync-PurchaseOrders.ps1 ===" -ForegroundColor Cyan
Write-Host "Confirme que DL_Taux1, DL_Remise01, DL_UniteVente (F_DOCLIGNE) et AR_FamilleCode (F_ARTICLE) existent bien sous ces noms." -ForegroundColor DarkGray
Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_DOCLIGNE' AND COLUMN_NAME IN ('DL_Taux1', 'DL_CodeTaxe1', 'DL_Remise01', 'DL_UniteVente', 'DL_Unite')" |
    Format-Table -AutoSize
Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_ARTICLE' AND COLUMN_NAME LIKE '%Famille%'" |
    Format-Table -AutoSize

Write-Host "`n=== 10. Facture fournisseur rattachee au BC (DO_FactureFrs / DO_FactureFile) ===" -ForegroundColor Cyan
Write-Host "Aucun DO_Type dedie 'facture' cote Achats : la facture prestataire est probablement" -ForegroundColor DarkGray
Write-Host "rattachee directement au bon de commande via ces colonnes de F_DOCENTETE." -ForegroundColor DarkGray
Invoke-Query $conn @"
SELECT TOP 20 DO_Piece, DO_Date, DO_Tiers, DO_FactureFrs, DO_NbFacture, DO_FactureFile, DO_FactureElec, DO_BLFact
FROM F_DOCENTETE
WHERE DO_Domaine = 1 AND DO_Type = 12 AND DO_FactureFrs IS NOT NULL AND DO_FactureFrs <> ''
ORDER BY DO_Date DESC
"@ | Format-Table -AutoSize

Write-Host "`n--- 10bis. Volume de BC avec/sans facture fournisseur renseignee ---" -ForegroundColor Cyan
Invoke-Query $conn @"
SELECT
    SUM(CASE WHEN DO_FactureFrs IS NOT NULL AND DO_FactureFrs <> '' THEN 1 ELSE 0 END) AS AvecFactureFrs,
    SUM(CASE WHEN DO_FactureFrs IS NULL OR DO_FactureFrs = '' THEN 1 ELSE 0 END) AS SansFactureFrs,
    SUM(CASE WHEN DO_FactureFile IS NOT NULL THEN 1 ELSE 0 END) AS AvecFactureFile
FROM F_DOCENTETE
WHERE DO_Domaine = 1 AND DO_Type = 12
"@ | Format-Table -AutoSize

Write-Host "`n--- 10ter. Table(s) media candidates pour le fichier joint (F_DOCENTETEMEDIA...) ---" -ForegroundColor Cyan
$mediaTables = Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%MEDIA%' OR TABLE_NAME LIKE '%DOCUMENT%' OR TABLE_NAME LIKE '%PIECEJOINTE%' ORDER BY TABLE_NAME"
$mediaTables | Format-Table -AutoSize
foreach ($mt in $mediaTables) {
    $tableName = $mt.TABLE_NAME
    Write-Host "`n--- Colonnes de $tableName ---" -ForegroundColor Magenta
    Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$tableName' ORDER BY ORDINAL_POSITION" |
        Format-Table -AutoSize
}

Write-Host "`n=== 11. Pieces jointes des BC dans F_DOCENTETEMEDIA (GED Sage) ===" -ForegroundColor Cyan
Write-Host "DO_FactureFrs est vide sur tous les BC (voir section 10bis) : la facture prestataire" -ForegroundColor DarkGray
Write-Host "est plus probablement un fichier joint (scan/PDF) sur le BC via F_DOCENTETEMEDIA." -ForegroundColor DarkGray
if (Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'F_DOCENTETEMEDIA'" | Select-Object -First 1) {
    Write-Host "`n--- 11a. Repartition des pieces jointes par DO_Type (tous domaines confondus) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT DO_Type, COUNT(*) AS NbPiecesJointes, COUNT(DISTINCT DO_Piece) AS NbDocumentsDistincts
FROM F_DOCENTETEMEDIA
GROUP BY DO_Type
ORDER BY DO_Type
"@ | Format-Table -AutoSize

    Write-Host "`n--- 11b. Echantillon de pieces jointes pour chaque DO_Type rencontre (10/12/16/17) ---" -ForegroundColor Cyan
    foreach ($t in 10, 12, 16, 17) {
        Write-Host "`n    DO_Type = $t :" -ForegroundColor Magenta
        Invoke-Query $conn @"
SELECT TOP 10 DO_Piece, DO_Type, DM_Intitule, DM_Fichier, DM_TypeMIME, DM_Origine, DM_GedId, cbCreation
FROM F_DOCENTETEMEDIA
WHERE DO_Type = $t
ORDER BY cbCreation DESC
"@ | Format-Table -AutoSize
    }

    Write-Host "`n--- 11c. Recherche 'facture' OU 'prestataire' dans DM_Intitule/DM_Fichier (toutes pieces confondues) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT TOP 30 DO_Piece, DO_Type, DM_Intitule, DM_Fichier, DM_TypeMIME, cbCreation
FROM F_DOCENTETEMEDIA
WHERE DM_Intitule LIKE '%facture%' OR DM_Fichier LIKE '%facture%'
   OR DM_Intitule LIKE '%Facture%' OR DM_Fichier LIKE '%Facture%'
   OR DM_Intitule LIKE '%prestataire%' OR DM_Fichier LIKE '%prestataire%'
   OR DM_Intitule LIKE '%Prestataire%' OR DM_Fichier LIKE '%Prestataire%'
ORDER BY cbCreation DESC
"@ | Format-Table -AutoSize

    Write-Host "`n--- 11d. Toutes les valeurs distinctes de DM_Origine (pour comprendre comment ces fichiers sont arrives) ---" -ForegroundColor Cyan
    Invoke-Query $conn "SELECT DISTINCT DM_Origine, COUNT(*) AS Nombre FROM F_DOCENTETEMEDIA GROUP BY DM_Origine" | Format-Table -AutoSize
} else {
    Write-Host "Table F_DOCENTETEMEDIA absente." -ForegroundColor Yellow
}

Write-Host "`n--- 11e. Recherche 'prestataire' dans les tiers F_COMPTET (CT_Intitule/CT_Qualite) ---" -ForegroundColor Cyan
Write-Host "Le client parle peut-etre du tiers lui-meme (le prestataire), pas d'un document." -ForegroundColor DarkGray
Invoke-Query $conn @"
SELECT TOP 30 CT_Num, CT_Intitule, CT_Type, CT_Qualite
FROM F_COMPTET
WHERE CT_Intitule LIKE '%prestataire%' OR CT_Intitule LIKE '%Prestataire%'
   OR CT_Qualite LIKE '%prestataire%' OR CT_Qualite LIKE '%Prestataire%'
"@ | Format-Table -AutoSize

Write-Host "`n=== 12. DO_Type utilises specifiquement par les tiers 'PRESTATAIRE...' ===" -ForegroundColor Cyan
Write-Host "Verifie si les prestataires passent TOUJOURS par DO_Type=12, ou parfois par un autre type." -ForegroundColor DarkGray
Invoke-Query $conn @"
SELECT d.DO_Domaine, d.DO_Type, COUNT(*) AS Nombre, MIN(d.DO_Piece) AS ExemplePiece
FROM F_DOCENTETE d
INNER JOIN F_COMPTET ct ON ct.CT_Num = d.DO_Tiers
WHERE ct.CT_Intitule LIKE '%PRESTATAIRE%'
GROUP BY d.DO_Domaine, d.DO_Type
ORDER BY d.DO_Domaine, d.DO_Type
"@ | Format-Table -AutoSize

Write-Host "`n=== 13. Colonne DO_Souche sur F_DOCENTETE (le dev Sage annonce 0=achat / 1=service) ===" -ForegroundColor Cyan
$souche = Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_DOCENTETE' AND COLUMN_NAME = 'DO_Souche'"
if ($souche | Select-Object -First 1) {
    $souche | Format-Table -AutoSize

    Write-Host "`n--- 13a. Repartition DO_Domaine/DO_Type/DO_Souche (pour situer 0=achat / 1=service) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT DO_Domaine, DO_Type, DO_Souche, COUNT(*) AS Nombre, MIN(DO_Piece) AS ExemplePiece
FROM F_DOCENTETE
WHERE DO_Domaine = 1 AND DO_Type = 12
GROUP BY DO_Domaine, DO_Type, DO_Souche
ORDER BY DO_Domaine, DO_Type, DO_Souche
"@ | Format-Table -AutoSize

    Write-Host "`n--- 13b. Croisement DO_Souche avec les tiers 'PRESTATAIRE...' (pour verifier 1=service) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT d.DO_Souche, COUNT(*) AS Nombre, SUM(CASE WHEN ct.CT_Intitule LIKE '%PRESTATAIRE%' THEN 1 ELSE 0 END) AS NbPrestataires
FROM F_DOCENTETE d
LEFT JOIN F_COMPTET ct ON ct.CT_Num = d.DO_Tiers
WHERE d.DO_Domaine = 1 AND d.DO_Type = 12
GROUP BY d.DO_Souche
ORDER BY d.DO_Souche
"@ | Format-Table -AutoSize
} else {
    Write-Host "Colonne DO_Souche absente de F_DOCENTETE chez ce client : l'info du dev Sage ne s'applique" -ForegroundColor Yellow
    Write-Host "peut-etre qu'a une autre version/parametrage. Verifier avec lui le nom exact de la colonne." -ForegroundColor Yellow
}

Write-Host "`n=== 14. Colonne DO_CodeService sur F_DOCENTETE (piste alternative pour achat vs service) ===" -ForegroundColor Cyan
Write-Host "DO_Souche vaut toujours 0 sur les BC chez ce client (section 13) : ne distingue rien ici." -ForegroundColor DarkGray
Write-Host "DO_CodeService est repere en section 2, son nom suggere un lien avec la notion 'service'." -ForegroundColor DarkGray
$codeService = Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_DOCENTETE' AND COLUMN_NAME = 'DO_CodeService'"
if ($codeService | Select-Object -First 1) {
    $codeService | Format-Table -AutoSize

    Write-Host "`n--- 14a. Valeurs distinctes de DO_CodeService sur les BC fournisseur (DO_Domaine=1, DO_Type=12) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT DO_CodeService, COUNT(*) AS Nombre, MIN(DO_Piece) AS ExemplePiece
FROM F_DOCENTETE
WHERE DO_Domaine = 1 AND DO_Type = 12
GROUP BY DO_CodeService
ORDER BY Nombre DESC
"@ | Format-Table -AutoSize

    Write-Host "`n--- 14b. Croisement DO_CodeService avec les tiers 'PRESTATAIRE...' (pour verifier si ca isole les services) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT d.DO_CodeService, COUNT(*) AS Nombre, SUM(CASE WHEN ct.CT_Intitule LIKE '%PRESTATAIRE%' THEN 1 ELSE 0 END) AS NbPrestataires,
       MIN(CASE WHEN ct.CT_Intitule LIKE '%PRESTATAIRE%' THEN ct.CT_Intitule END) AS ExemplePrestataire
FROM F_DOCENTETE d
LEFT JOIN F_COMPTET ct ON ct.CT_Num = d.DO_Tiers
WHERE d.DO_Domaine = 1 AND d.DO_Type = 12
GROUP BY d.DO_CodeService
ORDER BY d.DO_CodeService
"@ | Format-Table -AutoSize

    Write-Host "`n--- 14c. Echantillon de BC avec DO_CodeService renseigne (pour voir a quoi ca ressemble) ---" -ForegroundColor Cyan
    Invoke-Query $conn @"
SELECT TOP 20 DO_Piece, DO_Date, DO_Tiers, DO_CodeService
FROM F_DOCENTETE
WHERE DO_Domaine = 1 AND DO_Type = 12 AND DO_CodeService IS NOT NULL AND DO_CodeService <> ''
ORDER BY DO_Date DESC
"@ | Format-Table -AutoSize
} else {
    Write-Host "Colonne DO_CodeService absente de F_DOCENTETE chez ce client." -ForegroundColor Yellow
}

$conn.Close()
Write-Host "`n=== Termine ===" -ForegroundColor Green
Write-Host "Copie/colle le resultat de la section 4 (DO_Type), 5 (echantillon), 13 (DO_Souche) et 14" -ForegroundColor Green
Write-Host "(DO_CodeService) pour qu'on identifie ensemble le bon critere achat/service." -ForegroundColor Green
