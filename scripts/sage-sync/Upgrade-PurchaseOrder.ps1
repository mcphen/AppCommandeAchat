<#
.SYNOPSIS
    Rattrapage : rattache le chantier (DO_Ref) aux bons de commande fournisseur
    deja importes dans l'app mais sans chantier (project_id NULL).

.DESCRIPTION
    Le sync principal (Sync-PurchaseOrders.ps1) laisse le chantier desactive par
    defaut (-ProjectCodeColumn "") tant que l'hypothese "DO_Ref = code chantier"
    n'est pas confirmee sur un echantillon plus large (cf. README.md). Ce script
    sert de rattrapage ponctuel une fois l'hypothese validee : il demande a l'app
    quels BC (deja importes) n'ont pas encore de chantier, va chercher DO_Ref cote
    Sage100 pour chacun, et renvoie la valeur trouvee a l'app via un endpoint
    dedie (PATCH .../project) sans repasser tout le payload webhook. Ne modifie
    JAMAIS la base Sage100 (lecture seule).

.PARAMETER SqlServer
    Instance SQL Server Sage100 (ex: "localhost\SAGE100").

.PARAMETER Database
    Base Sage100 (ex: "CONSTRUCSEN2024").

.PARAMETER SqlUser / SqlPassword
    Identifiants SQL. Si omis, authentification Windows integree.

.PARAMETER ApiBaseUrl
    URL de base de l'application Laravel (ex: "https://achats.exemple.com").

.PARAMETER ApiToken
    Valeur de SAGE_API_TOKEN configuree cote Laravel (.env), envoyee en header X-API-Key.

.PARAMETER DryRun
    N'envoie rien : affiche seulement ce qui serait mis a jour (pour tester sans risque).

.EXAMPLE
    .\Upgrade-PurchaseOrder.ps1 -SqlServer "localhost\SQL2019" -Database "CONSTRUCSEN2024" `
        -ApiBaseUrl "https://achats.construcsen.com" -ApiToken "xxxxx" -DryRun

.EXAMPLE
    .\Upgrade-PurchaseOrder.ps1 -SqlServer "localhost\SQL2019" -Database "CONSTRUCSEN2024" `
        -ApiBaseUrl "https://achats.construcsen.com" -ApiToken "xxxxx"
#>

param(
    [Parameter(Mandatory = $true)]
    [string]$SqlServer,

    [Parameter(Mandatory = $true)]
    [string]$Database,

    [string]$SqlUser,
    [string]$SqlPassword,

    [Parameter(Mandatory = $true)]
    [string]$ApiBaseUrl,

    [Parameter(Mandatory = $true)]
    [string]$ApiToken,

    [switch]$DryRun
)

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$ErrorActionPreference = "Stop"
$LogFile = Join-Path $PSScriptRoot "upgrade-purchase-order.log"

function Write-Log([string]$Message, [string]$Level = "INFO") {
    $line = "[{0}] [{1}] {2}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Level, $Message
    Write-Host $line
    Add-Content -Path $LogFile -Value $line
}

function Get-SqlConnection {
    if ($SqlUser) {
        $connString = "Server=$SqlServer;Database=$Database;User Id=$SqlUser;Password=$SqlPassword;TrustServerCertificate=True;"
    } else {
        $connString = "Server=$SqlServer;Database=$Database;Integrated Security=True;TrustServerCertificate=True;"
    }
    $conn = New-Object System.Data.SqlClient.SqlConnection($connString)
    $conn.Open()
    return $conn
}

function Invoke-SqlQuery([System.Data.SqlClient.SqlConnection]$Connection, [string]$Sql) {
    $cmd = New-Object System.Data.SqlClient.SqlCommand($Sql, $Connection)
    $cmd.CommandTimeout = 60
    $adapter = New-Object System.Data.SqlClient.SqlDataAdapter($cmd)
    $table = New-Object System.Data.DataTable
    [void]$adapter.Fill($table)
    return $table
}

# Trim() plante sur DBNull/$null : frequent sur de vraies donnees de prod (champs vides en Sage).
function Get-SafeTrim($value) {
    if ($null -eq $value -or $value -is [System.DBNull]) { return "" }
    return [string]$value.Trim()
}

# ── 1. Demander a l'app la liste des BC deja importes mais sans chantier ───────
Write-Log "=== Debut rattrapage chantier (DryRun=$($DryRun.IsPresent)) ==="

try {
    $missing = Invoke-RestMethod -Uri "$ApiBaseUrl/api/sage/purchase-orders/missing-project" `
        -Method Get `
        -Headers @{ "X-API-Key" = $ApiToken } `
        -TimeoutSec 60
} catch {
    Write-Log "Echec de l'appel a $ApiBaseUrl/api/sage/purchase-orders/missing-project : $($_.Exception.Message)" "ERROR"
    exit 1
}

$numeros = @($missing.numeros)
Write-Log "BC sans chantier cote app : $($numeros.Count)"

if ($numeros.Count -eq 0) {
    Write-Log "=== Rien a mettre a jour. Fin. ==="
    exit 0
}

# ── 2. Connexion Sage et lecture de TOUS les DO_Ref des BC (une seule requete, ──
# pas de boucle piece-par-piece, cf. bug connu documente dans Sync-PurchaseOrders.ps1) ─
try {
    $conn = Get-SqlConnection
} catch {
    $innerMessage = $_.Exception.InnerException.Message
    if (-not $innerMessage) { $innerMessage = $_.Exception.Message }
    Write-Log "Echec de connexion SQL : $innerMessage" "ERROR"
    exit 1
}

$entetes = Invoke-SqlQuery $conn @"
SELECT DO_Piece, DO_Ref
FROM F_DOCENTETE
WHERE DO_Domaine = 1 AND DO_Type = 12
"@
$conn.Close()

$chantierParPiece = @{}
foreach ($row in $entetes.Rows) {
    $piece = Get-SafeTrim $row.DO_Piece
    if (-not $piece) { continue }
    $chantierParPiece[$piece] = Get-SafeTrim $row.DO_Ref
}

# ── 3. Pour chaque BC sans chantier cote app, chercher DO_Ref cote Sage et renvoyer ──
$successCount = 0
$skippedCount = 0
$errorCount = 0

foreach ($numero in $numeros) {
    $numero = [string]$numero
    if (-not $chantierParPiece.ContainsKey($numero)) {
        Write-Log "  $numero introuvable dans Sage (BC_Domaine=1/Type=12), ignore." "WARN"
        $skippedCount++
        continue
    }

    $chantier = $chantierParPiece[$numero]
    if (-not $chantier) {
        Write-Log "  $numero : DO_Ref vide cote Sage, ignore." "WARN"
        $skippedCount++
        continue
    }

    if ($DryRun) {
        Write-Log "  [DRY-RUN] $numero -> chantier '$chantier'"
        $successCount++
        continue
    }

    $body = @{ projet_code = $chantier } | ConvertTo-Json
    $utf8Bytes = [System.Text.Encoding]::UTF8.GetBytes($body)

    try {
        $response = Invoke-RestMethod -Uri "$ApiBaseUrl/api/sage/purchase-orders/$numero/project" `
            -Method Patch `
            -Headers @{ "X-API-Key" = $ApiToken } `
            -ContentType "application/json; charset=utf-8" `
            -Body $utf8Bytes `
            -TimeoutSec 60

        Write-Log "  OK -> $numero rattache au chantier '$($response.project.name)'"
        $successCount++
    } catch {
        $statusCode = $null
        if ($_.Exception.Response) { $statusCode = [int]$_.Exception.Response.StatusCode }
        Write-Log "  ECHEC (HTTP $statusCode) pour $numero : $($_.Exception.Message)" "ERROR"
        $errorCount++
    }

    Start-Sleep -Milliseconds 300
}

Write-Log "=== Fin rattrapage : $successCount succes, $errorCount echec(s), $skippedCount ignore(s) ==="
if ($errorCount -gt 0) { exit 2 }
