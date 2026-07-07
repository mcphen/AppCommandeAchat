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
$conn = New-Connection
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

Write-Host "`n=== 4. Repartition des DO_Type (pour identifier 'commande fournisseur') ===" -ForegroundColor Cyan
Invoke-Query $conn @"
SELECT DO_Type, COUNT(*) AS Nombre, MIN(DO_Date) AS PremiereDate, MAX(DO_Date) AS DerniereDate
FROM F_DOCENTETE
GROUP BY DO_Type
ORDER BY DO_Type
"@ | Format-Table -AutoSize

Write-Host "`n=== 5. Echantillon de 5 lignes recentes par DO_Type ===" -ForegroundColor Cyan
$types = Invoke-Query $conn "SELECT DISTINCT DO_Type FROM F_DOCENTETE"
foreach ($row in $types) {
    $type = $row.DO_Type
    Write-Host "`n--- DO_Type = $type ---" -ForegroundColor Magenta
    Invoke-Query $conn @"
SELECT TOP 5 DO_Piece, DO_Date, DO_Tiers, DO_Ref, DO_Type
FROM F_DOCENTETE
WHERE DO_Type = '$type'
ORDER BY DO_Date DESC
"@ | Format-Table -AutoSize
}

Write-Host "`n=== 6. Table des tiers (fournisseurs) : F_COMPTET, colonnes cle ===" -ForegroundColor Cyan
if (Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'F_COMPTET'" | Select-Object -First 1) {
    Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_COMPTET' ORDER BY ORDINAL_POSITION" |
        Format-Table -AutoSize
    Invoke-Query $conn "SELECT TOP 5 CT_Num, CT_Intitule, CT_Type FROM F_COMPTET ORDER BY CT_Num" | Format-Table -AutoSize
}

Write-Host "`n=== 7. Table des articles : F_ARTICLE, colonnes cle ===" -ForegroundColor Cyan
if (Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'F_ARTICLE'" | Select-Object -First 1) {
    Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_ARTICLE' ORDER BY ORDINAL_POSITION" |
        Format-Table -AutoSize
    Invoke-Query $conn "SELECT TOP 5 AR_Ref, AR_Design FROM F_ARTICLE ORDER BY AR_Ref" | Format-Table -AutoSize
}

$conn.Close()
Write-Host "`n=== Termine ===" -ForegroundColor Green
Write-Host "Copie/colle le resultat de la section 4 (DO_Type) et 5 (echantillon) pour qu'on identifie" -ForegroundColor Green
Write-Host "ensemble quel DO_Type correspond aux bons de commande fournisseur." -ForegroundColor Green
