<#
.SYNOPSIS
    Explore comment un article Sage100 (F_ARTICLE) se relie a ses comptes comptables
    (compta) et a son suivi de stock, en vue d'une future integration.

.DESCRIPTION
    Lecture seule. A executer sur la vraie base (ex: CONSTRUCSEN2024 sur SQL2019).

.EXAMPLE
    .\Discover-ArticleLinks.ps1 -SqlServer "localhost\SQL2019" -Database "CONSTRUCSEN2024"
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
    exit 1
}
Write-Host "Connecte." -ForegroundColor Green

Write-Host "`n=== 1. Tables candidates liees aux articles (famille, stock, compta) ===" -ForegroundColor Cyan
$tables = Invoke-Query $conn @"
SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_NAME LIKE 'F_FAMILLE%'
   OR TABLE_NAME LIKE 'F_ARTSTOCK%'
   OR TABLE_NAME LIKE 'F_STOCK%'
   OR TABLE_NAME LIKE 'F_ARTFOURNISS%'
   OR TABLE_NAME LIKE 'F_COMPTEGEN%'
   OR TABLE_NAME LIKE 'F_ARTCLASS%'
   OR TABLE_NAME LIKE 'F_CLASSIFICATION%'
   OR TABLE_NAME LIKE '%DEPOT%'
ORDER BY TABLE_NAME
"@
$tables | Format-Table -AutoSize

Write-Host "`n=== 2. Colonnes cle de F_ARTICLE liees a la famille/classification/compta ===" -ForegroundColor Cyan
Invoke-Query $conn @"
SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'F_ARTICLE'
  AND (COLUMN_NAME LIKE '%Famille%' OR COLUMN_NAME LIKE 'CL_No%' OR COLUMN_NAME LIKE '%Compt%' OR COLUMN_NAME LIKE '%Compta%')
ORDER BY ORDINAL_POSITION
"@ | Format-Table -AutoSize

if (Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'F_FAMILLE'" | Select-Object -First 1) {
    Write-Host "`n=== 3. F_FAMILLE : colonnes et echantillon (comptes comptables par famille ?) ===" -ForegroundColor Cyan
    Invoke-Query $conn "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'F_FAMILLE' ORDER BY ORDINAL_POSITION" |
        Format-Table -AutoSize
    Invoke-Query $conn "SELECT TOP 10 * FROM F_FAMILLE" | Format-Table -AutoSize
} else {
    Write-Host "`n=== 3. Table F_FAMILLE introuvable sous ce nom exact ===" -ForegroundColor Yellow
}

Write-Host "`n=== 4. Exemple : un article avec sa famille et ses comptes comptables associes ===" -ForegroundColor Cyan
Invoke-Query $conn @"
SELECT TOP 10 a.AR_Ref, a.AR_Design, a.FA_CodeFamille, a.CL_No1, a.CL_No2, a.CL_No3, a.CL_No4
FROM F_ARTICLE a
WHERE a.FA_CodeFamille IS NOT NULL AND a.FA_CodeFamille <> ''
ORDER BY a.AR_Ref
"@ | Format-Table -AutoSize

Write-Host "`n=== 5. Tables de classification (CL_No1-4 -> F_CLASSIFICATION eventuelle) ===" -ForegroundColor Cyan
Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%CLASS%'" | Format-Table -AutoSize

Write-Host "`n=== 6. Suivi de stock : tables et lien vers AR_Ref ===" -ForegroundColor Cyan
Invoke-Query $conn "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE 'F_ARTMVT%' OR TABLE_NAME LIKE 'F_MVTSTOCK%' OR TABLE_NAME LIKE 'F_STOCKDEPOT%'" |
    Format-Table -AutoSize

$conn.Close()
Write-Host "`n=== Termine ===" -ForegroundColor Green
Write-Host "Copie/colle tout le resultat pour qu'on identifie ensemble comment achats/compta/stock" -ForegroundColor Green
Write-Host "se rattachent au meme article (ou pas) dans cette instance Sage100." -ForegroundColor Green
