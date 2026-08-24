<#
.SYNOPSIS
    Affiche les 5 premieres lignes brutes de F_DOCENTETE (Domaine=1/Type=12) avec le
    type .NET exact de DO_Piece, pour comprendre pourquoi Get-SafeTrim le voit comme
    vide/null dans Upgrade-PurchaseOrder.ps1. Lecture seule.

.EXAMPLE
    .\Diagnose-RawColumn.ps1 -SqlServer "localhost\SQL2019" -Database "CONSTRUCSEN2024"
#>
param(
    [Parameter(Mandatory = $true)][string]$SqlServer,
    [Parameter(Mandatory = $true)][string]$Database,
    [string]$SqlUser,
    [string]$SqlPassword
)

if ($SqlUser) {
    $connString = "Server=$SqlServer;Database=$Database;User Id=$SqlUser;Password=$SqlPassword;TrustServerCertificate=True;"
} else {
    $connString = "Server=$SqlServer;Database=$Database;Integrated Security=True;TrustServerCertificate=True;"
}
$conn = New-Object System.Data.SqlClient.SqlConnection($connString)
$conn.Open()

$cmd = New-Object System.Data.SqlClient.SqlCommand("SELECT TOP 5 DO_Piece, DO_Ref FROM F_DOCENTETE WHERE DO_Domaine = 1 AND DO_Type = 12", $conn)
$adapter = New-Object System.Data.SqlClient.SqlDataAdapter($cmd)
$table = New-Object System.Data.DataTable
[void]$adapter.Fill($table)
$conn.Close()

Write-Host "Colonnes de la DataTable : $($table.Columns | ForEach-Object { $_.ColumnName } | Out-String)"

$i = 0
foreach ($row in $table.Rows) {
    $i++
    Write-Host "--- Ligne $i ---"
    Write-Host "  row.DO_Piece (dot)      = [$($row.DO_Piece)]  Type=$($row.DO_Piece.GetType().FullName)"
    Write-Host "  row['DO_Piece'] (index) = [$($row['DO_Piece'])]  Type=$($row['DO_Piece'].GetType().FullName)"
    Write-Host "  IsNull(DO_Piece)        = $($row.IsNull('DO_Piece'))"
}

Write-Host "`n--- via ForEach-Object (pipeline, comme Get-SafeTrim pourrait etre appele) ---"
$table.Rows | Select-Object -First 5 | ForEach-Object {
    Write-Host "  Type de \$_ dans le pipeline : $($_.GetType().FullName)"
    Write-Host "  \$_.DO_Piece : [$($_.DO_Piece)]"
}
