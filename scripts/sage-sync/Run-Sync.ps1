<#
.SYNOPSIS
    Wrapper appele par le Planificateur de taches Windows : lit les parametres
    (dont le token API) depuis sage-sync.config.json et appelle Sync-PurchaseOrders.ps1.

.DESCRIPTION
    Objectif : ne jamais exposer le token API en clair dans la definition de la tache
    planifiee (visible par tout administrateur via le Planificateur de taches).
    Le fichier sage-sync.config.json doit exister a cote de ce script (copie de
    sage-sync.config.example.json, rempli avec les vraies valeurs) et doit avoir des
    permissions NTFS restreintes (voir README ci-dessous).

.EXAMPLE
    .\Run-Sync.ps1
    .\Run-Sync.ps1 -DryRun
#>

param(
    [switch]$DryRun
)

$configPath = Join-Path $PSScriptRoot "sage-sync.config.json"

if (-not (Test-Path $configPath)) {
    Write-Error "Fichier de config introuvable : $configPath. Copie sage-sync.config.example.json vers sage-sync.config.json et remplis les vraies valeurs."
    exit 1
}

$config = Get-Content $configPath -Raw | ConvertFrom-Json

$params = @{
    SqlServer  = $config.SqlServer
    Database   = $config.Database
    ApiBaseUrl = $config.ApiBaseUrl
    ApiToken   = $config.ApiToken
}

if ($config.SqlUser) {
    $params.SqlUser = $config.SqlUser
    $params.SqlPassword = $config.SqlPassword
}

if ($DryRun) { $params.DryRun = $true }

& (Join-Path $PSScriptRoot "Sync-PurchaseOrders.ps1") @params
exit $LASTEXITCODE
