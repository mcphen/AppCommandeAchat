<#
.SYNOPSIS
    Wrapper appele manuellement : lit les parametres (dont le token API) depuis
    sage-sync.config.json et appelle Upgrade-PurchaseOrder.ps1.

.DESCRIPTION
    Meme fichier de config que Run-Sync.ps1 (memes cles SqlServer/Database/
    ApiBaseUrl/ApiToken) : evite de retaper/exposer le token API en clair dans
    le terminal a chaque rattrapage ponctuel.

.EXAMPLE
    .\Run-Upgrade.ps1 -DryRun
    .\Run-Upgrade.ps1
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

& (Join-Path $PSScriptRoot "Upgrade-PurchaseOrder.ps1") @params
exit $LASTEXITCODE
