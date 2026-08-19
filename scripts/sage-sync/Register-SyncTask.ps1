<#
.SYNOPSIS
    Cree (ou met a jour) la tache planifiee Windows qui execute Run-Sync.ps1
    toutes les minutes, en boucle continue (equivalent d'un cron "* * * * *").

.DESCRIPTION
    A executer UNE FOIS sur le serveur Windows de prod, en PowerShell lance
    "en tant qu'administrateur". Idempotent : relancer ce script met juste a
    jour la tache existante plutot que d'en creer une deuxieme.

.EXAMPLE
    .\Register-SyncTask.ps1
    .\Register-SyncTask.ps1 -TaskName "Sync-Sage-PurchaseOrders" -IntervalMinutes 1
#>

param(
    [string]$TaskName = "Sync-Sage-PurchaseOrders",
    [int]$IntervalMinutes = 1,
    [string]$ScriptPath = (Join-Path $PSScriptRoot "Run-Sync.ps1")
)

if (-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Error "Ce script doit etre lance dans une console PowerShell 'Executer en tant qu'administrateur'."
    exit 1
}

if (-not (Test-Path $ScriptPath)) {
    Write-Error "Introuvable : $ScriptPath"
    exit 1
}

$action = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`"" `
    -WorkingDirectory $PSScriptRoot

# RepetitionDuration ([TimeSpan]::MaxValue) plante le Planificateur (depasse la
# limite XML) -> utiliser une duree tres longue a la place (voir README, etape 5).
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes $IntervalMinutes) `
    -RepetitionDuration (New-TimeSpan -Days 3650)

# Empeche deux executions en parallele si un run depasse 1 minute (ex: lenteur
# reseau/API) : la tache suivante est ignoree plutot que lancee en double.
$settings = New-ScheduledTaskSettingsSet `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5) `
    -RestartCount 3 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -StartWhenAvailable `
    -DontStopOnIdleEnd

# Executee sous SYSTEM : pas de mot de passe a stocker/renouveler, mais bien
# verifier au prealable que le compte SYSTEM a les droits sur la base SQL Sage
# (voir README etape 1 - un compte interactif qui marche ne garantit rien pour SYSTEM).
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

if (Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue) {
    Write-Host "Tache '$TaskName' existante -> mise a jour."
    Set-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal | Out-Null
} else {
    Write-Host "Creation de la tache '$TaskName'."
    Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings -Principal $principal | Out-Null
}

Write-Host "OK. Verification :"
Get-ScheduledTask -TaskName $TaskName | Select-Object TaskName, State
Get-ScheduledTaskInfo -TaskName $TaskName | Format-List NextRunTime, LastRunTime, LastTaskResult
