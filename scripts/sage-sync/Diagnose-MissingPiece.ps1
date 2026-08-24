<#
.SYNOPSIS
    Diagnostic ponctuel : pourquoi Upgrade-PurchaseOrder.ps1 ne retrouve pas un
    DO_Piece donne cote Sage. Ne modifie rien, lecture seule.

.EXAMPLE
    .\Diagnose-MissingPiece.ps1 -SqlServer "localhost\SQL2019" -Database "CONSTRUCSEN2024" -Piece "BC4167"
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

function Query([string]$sql) {
    $cmd = New-Object System.Data.SqlClient.SqlCommand($sql, $conn)
    $adapter = New-Object System.Data.SqlClient.SqlDataAdapter($cmd)
    $table = New-Object System.Data.DataTable
    [void]$adapter.Fill($table)
    return $table
}

Write-Host "=== 1. Correspondance EXACTE (DO_Piece = '$Piece') tous domaines/types ==="
Query "SELECT DO_Piece, DO_Domaine, DO_Type, DO_Ref, LEN(DO_Piece) AS Longueur, cbModification FROM F_DOCENTETE WHERE DO_Piece = '$Piece'" | Format-Table -AutoSize

Write-Host "=== 2. Correspondance APPROCHEE (RTRIM/LTRIM + LIKE) tous domaines/types ==="
Query "SELECT DO_Piece, DO_Domaine, DO_Type, DO_Ref, LEN(DO_Piece) AS Longueur FROM F_DOCENTETE WHERE LTRIM(RTRIM(DO_Piece)) LIKE '%$Piece%'" | Format-Table -AutoSize

Write-Host "=== 3. Nombre total de lignes F_DOCENTETE en Domaine=1/Type=12 ==="
Query "SELECT COUNT(*) AS NbCommandesFournisseur FROM F_DOCENTETE WHERE DO_Domaine = 1 AND DO_Type = 12" | Format-Table -AutoSize

$conn.Close()
