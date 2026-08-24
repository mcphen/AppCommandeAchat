<#
.SYNOPSIS
    Reproduit EXACTEMENT la requete + hashtable de Upgrade-PurchaseOrder.ps1 pour
    isoler pourquoi une piece presente en Sage (confirmee par Diagnose-MissingPiece.ps1)
    est quand meme rapportee "introuvable" par le script principal. Lecture seule.

.EXAMPLE
    .\Diagnose-UpgradeMatch.ps1 -SqlServer "localhost\SQL2019" -Database "CONSTRUCSEN2024" -Piece "BC4167"
#>
param(
    [Parameter(Mandatory = $true)][string]$SqlServer,
    [Parameter(Mandatory = $true)][string]$Database,
    [string]$SqlUser,
    [string]$SqlPassword,
    [Parameter(Mandatory = $true)][string]$Piece
)

if ($SqlUser) {
    $connString = "Server=$SqlServer;Database=$Database;User Id=$SqlUser;Password=$SqlPassword;TrustServerCertificate=True;"
} else {
    $connString = "Server=$SqlServer;Database=$Database;Integrated Security=True;TrustServerCertificate=True;"
}
$conn = New-Object System.Data.SqlClient.SqlConnection($connString)
$conn.Open()

function Invoke-SqlQuery([System.Data.SqlClient.SqlConnection]$Connection, [string]$Sql) {
    $cmd = New-Object System.Data.SqlClient.SqlCommand($Sql, $Connection)
    $cmd.CommandTimeout = 60
    $adapter = New-Object System.Data.SqlClient.SqlDataAdapter($cmd)
    $table = New-Object System.Data.DataTable
    [void]$adapter.Fill($table)
    return $table
}

function Get-SafeTrim($value) {
    if ($null -eq $value -or $value -is [System.DBNull]) { return "" }
    return [string]$value.Trim()
}

$entetes = Invoke-SqlQuery $conn @"
SELECT DO_Piece, DO_Ref
FROM F_DOCENTETE
WHERE DO_Domaine = 1 AND DO_Type = 12
"@
$conn.Close()

Write-Host "Lignes recuperees par la requete : $($entetes.Rows.Count)"

$chantierParPiece = @{}
$rawSamples = @()
foreach ($row in $entetes.Rows) {
    $pieceTrim = Get-SafeTrim $row.DO_Piece
    if (-not $pieceTrim) { continue }
    $chantierParPiece[$pieceTrim] = Get-SafeTrim $row.DO_Ref
    if ($row.DO_Piece -match [regex]::Escape($Piece)) {
        $rawSamples += [PSCustomObject]@{
            RawValue    = $row.DO_Piece
            RawLength   = ([string]$row.DO_Piece).Length
            RawBytes    = ([System.Text.Encoding]::UTF8.GetBytes([string]$row.DO_Piece) -join ',')
            TrimmedValue = $pieceTrim
            TrimmedLength = $pieceTrim.Length
        }
    }
}

Write-Host "Cles dans la hashtable : $($chantierParPiece.Count)"
Write-Host "ContainsKey('$Piece') : $($chantierParPiece.ContainsKey($Piece))"

Write-Host "`n=== Lignes brutes matchant '$Piece' (avant/apres trim, octets UTF8) ==="
$rawSamples | Format-List

Write-Host "`n=== Test avec le meme texte tape ici en dur ==="
$hardcoded = "$Piece"
Write-Host "Longueur du texte tape ici : $($hardcoded.Length)"
Write-Host "ContainsKey avec texte tape en dur : $($chantierParPiece.ContainsKey($hardcoded))"
