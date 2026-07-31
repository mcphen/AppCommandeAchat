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

$conn.Close()
Write-Host "`n=== Termine ===" -ForegroundColor Green
Write-Host "Copie/colle le resultat de la section 4 (DO_Type) et 5 (echantillon) pour qu'on identifie" -ForegroundColor Green
Write-Host "ensemble quel DO_Type correspond aux bons de commande fournisseur." -ForegroundColor Green
