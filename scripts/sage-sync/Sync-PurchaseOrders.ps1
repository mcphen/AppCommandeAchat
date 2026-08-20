<#
.SYNOPSIS
    Synchronise les commandes fournisseur Sage100 (DO_Domaine=1, DO_Type=12) vers
    l'API webhook Laravel POST /api/sage/purchase-orders.

.DESCRIPTION
    Lit F_DOCENTETE/F_DOCLIGNE pour les commandes fournisseur nouvelles ou modifiees
    depuis le dernier passage (watermark sur cbModification), construit le JSON attendu
    par le webhook, l'envoie, puis avance le watermark uniquement pour les pieces
    envoyees avec succes. Les echecs restent dans la fenetre et seront retentes au
    prochain passage. Ne modifie JAMAIS la base Sage100 (lecture seule).

.PARAMETER SqlServer
    Instance SQL Server Sage100 (ex: "localhost\SAGE100").

.PARAMETER Database
    Base Sage100 (ex: "bijou" en test, la vraie base client en prod).

.PARAMETER SqlUser / SqlPassword
    Identifiants SQL. Si omis, authentification Windows integree.

.PARAMETER ApiBaseUrl
    URL de base de l'application Laravel (ex: "https://achats.exemple.com").

.PARAMETER ApiToken
    Valeur de SAGE_API_TOKEN configuree cote Laravel (.env), envoyee en header X-API-Key.

.PARAMETER StateFile
    Fichier local ou est stocke le watermark (date de derniere synchro reussie).
    Par defaut : sync-state.json a cote du script.

.PARAMETER DryRun
    N'envoie rien : affiche seulement ce qui serait synchronise (pour tester sans risque).

.EXAMPLE
    .\Sync-PurchaseOrders.ps1 -SqlServer "localhost\SAGE100" -Database "bijou" `
        -ApiBaseUrl "https://achats.exemple.com" -ApiToken "xxxxx" -DryRun

.EXAMPLE
    .\Sync-PurchaseOrders.ps1 -SqlServer "localhost\SAGE100" -Database "bijou" `
        -ApiBaseUrl "https://achats.exemple.com" -ApiToken "xxxxx"
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

    [string]$StateFile = (Join-Path $PSScriptRoot "sync-state.json"),

    # Nom reel de la colonne "code affaire/chantier" sur F_DOCENTETE. Desactive par
    # defaut (""). Chez Construcsen (2026-07-31) : pas de module "Affaires" (F_AFFAIRE
    # absente), mais DO_Ref contient deja des libelles ressemblant a des chantiers
    # ("SOMONE/PLOMBERIE", "SN HLM BAMBILOR"...) sur les BC (DO_Domaine=1, DO_Type=12) :
    # valeur suggeree -ProjectCodeColumn "DO_Ref", a confirmer sur un echantillon plus large
    # avant activation (c'est une hypothese semantique, pas une colonne dediee).
    [string]$ProjectCodeColumn = "",

    # Colonnes deja calculees par Sage pour le montant HT/TTC de la ligne (existent chez
    # Construcsen : DL_MontantHT / DL_MontantTTC sur F_DOCLIGNE). A partir de ces deux
    # montants (et de DL_Qte * DL_PrixUnitaire), le script deduit lui-meme un taux de TVA
    # et un taux de remise effectifs par ligne — plus fiable que d'interpreter DL_Taxe1 /
    # DL_Remise01REM_Valeur+REM_Type dont la semantique exacte (montant vs pourcentage,
    # type de taxe) n'est pas garantie. Desactivees par defaut ("").
    [string]$LineAmountHtColumn = "",   # valeur confirmee chez Construcsen : "DL_MontantHT"
    [string]$LineAmountTtcColumn = "",  # valeur confirmee chez Construcsen : "DL_MontantTTC"

    # Aucune colonne texte d'unite trouvee sur F_DOCLIGNE chez Construcsen (seulement
    # AR_UniteVen, un code numerique sur l'article renvoyant a une table de parametres
    # non exploree) : reste desactive tant qu'aucune source fiable n'est identifiee.
    [string]$SaleUnitColumn = "",

    # Confirme chez Construcsen : FA_CodeFamille (F_ARTICLE), PAS AR_FamilleCode.
    [string]$ArticleFamilyColumn = "",

    [switch]$DryRun
)

# PowerShell 5.1 / .NET Framework negocie parfois un TLS trop ancien par defaut
# (TLS 1.0), que beaucoup de serveurs web refusent silencieusement -> la requete
# echoue avec une erreur de connexion generique, jamais une vraie erreur HTTP.
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

$ErrorActionPreference = "Stop"
$LogFile = Join-Path $PSScriptRoot "sync.log"

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

function Invoke-SqlQuery([System.Data.SqlClient.SqlConnection]$Connection, [string]$Sql, [hashtable]$Params = @{}) {
    $cmd = New-Object System.Data.SqlClient.SqlCommand($Sql, $Connection)
    $cmd.CommandTimeout = 60
    foreach ($key in $Params.Keys) {
        [void]$cmd.Parameters.AddWithValue("@$key", $Params[$key])
    }
    $adapter = New-Object System.Data.SqlClient.SqlDataAdapter($cmd)
    $table = New-Object System.Data.DataTable
    [void]$adapter.Fill($table)
    return $table
}

function Get-Watermark {
    if (Test-Path $StateFile) {
        $state = Get-Content $StateFile -Raw | ConvertFrom-Json
        return [datetime]$state.lastCbModification
    }
    return [datetime]"2000-01-01"
}

function Set-Watermark([datetime]$Value) {
    @{ lastCbModification = $Value.ToString("o") } | ConvertTo-Json | Set-Content -Path $StateFile
}

# Trim() plante sur DBNull/$null : frequent sur de vraies donnees de prod (champs vides en Sage).
function Get-SafeTrim($value) {
    if ($null -eq $value -or $value -is [System.DBNull]) { return "" }
    return [string]$value.Trim()
}

function Get-SafeDouble($value) {
    if ($null -eq $value -or $value -is [System.DBNull]) { return 0.0 }
    return [double]$value
}

# Variantes "nullable" pour les champs optionnels (taux TVA, remise, code affaire...) ou
# 0/"" a un sens different de "non renseigne" : on veut vraiment $null en JSON, pas 0.
function Get-SafeNullableTrim($value) {
    if ($null -eq $value -or $value -is [System.DBNull]) { return $null }
    $trimmed = [string]$value.Trim()
    return $(if ($trimmed) { $trimmed } else { $null })
}

function Get-SafeNullableDouble($value) {
    if ($null -eq $value -or $value -is [System.DBNull]) { return $null }
    return [double]$value
}

# Lit une colonne "optionnelle" (activee/desactivee selon les parametres ci-dessus) sans
# jamais planter : si la colonne n'a pas ete selectionnee dans la requete (desactivee),
# $Row.Table.Columns ne la contient pas et un acces direct $Row['X'] leverait une erreur.
function Get-OptionalColumn($Row, [string]$ColumnName) {
    if ($Row.Table.Columns.Contains($ColumnName)) { return $Row[$ColumnName] }
    return $null
}

# Deduit un taux de TVA / remise effectif a partir des montants HT/TTC deja calcules par
# Sage (DL_MontantHT / DL_MontantTTC), plutot que d'interpreter des colonnes dont la
# semantique exacte (montant vs pourcentage, type de taxe) varie selon la config Sage100
# du client. Retourne $null si les montants necessaires ne sont pas disponibles.
function Get-LigneTauxTva($MontantHt, $MontantTtc) {
    if ($null -eq $MontantHt -or $null -eq $MontantTtc -or $MontantHt -le 0) { return $null }
    return [math]::Round((($MontantTtc / $MontantHt) - 1) * 100, 2)
}

function Get-LigneRemise($MontantHt, $Quantite, $PrixUnitaire) {
    if ($null -eq $MontantHt) { return $null }
    $brut = $Quantite * $PrixUnitaire
    if ($brut -le 0) { return $null }
    return [math]::Round((1 - ($MontantHt / $brut)) * 100, 2)
}

# ── 1. Connexion ────────────────────────────────────────────────────────────
Write-Log "=== Debut synchro (DryRun=$($DryRun.IsPresent)) ==="
try {
    $conn = Get-SqlConnection
} catch {
    $innerMessage = $_.Exception.InnerException.Message
    if (-not $innerMessage) { $innerMessage = $_.Exception.Message }
    Write-Log "Echec de connexion SQL : $innerMessage" "ERROR"
    exit 1
}

$watermark = Get-Watermark
Write-Log "Watermark actuel : $($watermark.ToString('o'))"

# ── 2. Recuperer les entetes de commandes fournisseur modifiees depuis le watermark ──
# Jointure F_COLLABORATEUR (via CO_No) pour le vrai demandeur, et F_COMPTET (via DO_Tiers)
# pour les vraies coordonnees du fournisseur (nom, adresse, ville, tel, email, siret).
# Colonne "code affaire" ajoutee dynamiquement via $ProjectCodeColumn (nom variable
# selon config Sage100 du client, cf. parametre en tete de script).
$projectCodeSelect = if ($ProjectCodeColumn) { ", d.$ProjectCodeColumn AS DO_ProjetCode" } else { "" }
$entetes = Invoke-SqlQuery $conn @"
SELECT d.DO_Piece, d.DO_Date, d.DO_Tiers, d.DO_TotalHT, d.DO_TotalTTC, d.cbModification, d.DO_Cloture,
       d.DO_Souche, d.CO_No, col.CO_Nom, col.CO_Prenom, col.CO_EMail,
       ct.CT_Intitule, ct.CT_Adresse, ct.CT_Ville, ct.CT_Telephone, ct.CT_EMail AS CT_Email, ct.CT_Siret
       $projectCodeSelect
FROM F_DOCENTETE d
LEFT JOIN F_COLLABORATEUR col ON col.CO_No = d.CO_No
LEFT JOIN F_COMPTET ct ON ct.CT_Num = d.DO_Tiers
WHERE d.DO_Domaine = 1 AND d.DO_Type = 12 AND d.cbModification > @watermark
ORDER BY d.cbModification ASC
"@ -Params @{ watermark = $watermark }

Write-Log "Commandes fournisseur a synchroniser : $($entetes.Rows.Count)"

if ($entetes.Rows.Count -eq 0) {
    $conn.Close()
    Write-Log "=== Rien a synchroniser. Fin. ==="
    exit 0
}

# ── 3. Recuperer TOUTES les lignes en une seule requete, puis grouper cote PowerShell ──
# (evite tout souci de correspondance de parametre SQL piece-par-piece)
# Colonnes montant HT/TTC/unite/famille ajoutees dynamiquement seulement si les parametres
# correspondants sont renseignes (desactivees par defaut, cf. parametres en tete de script).
# Alias fixes (AS DL_MontantHT / AS AR_FamilleCode / ...) pour que le reste du script
# puisse toujours reference le meme nom, quel que soit le nom reel de la colonne client.
$ligneExtraSelect = ""
if ($LineAmountHtColumn)  { $ligneExtraSelect += ", l.$LineAmountHtColumn AS DL_MontantHT" }
if ($LineAmountTtcColumn) { $ligneExtraSelect += ", l.$LineAmountTtcColumn AS DL_MontantTTC" }
if ($SaleUnitColumn)      { $ligneExtraSelect += ", l.$SaleUnitColumn AS DL_UniteVente" }

# Jointure F_ARTICLE (via AR_Ref) uniquement si necessaire, pour recuperer la famille
# article (AR_FamilleCode), utile pour rattacher automatiquement une categorie interne.
$articleJoin   = if ($ArticleFamilyColumn) { "LEFT JOIN F_ARTICLE ar ON ar.AR_Ref = l.AR_Ref" } else { "" }
$articleSelect = if ($ArticleFamilyColumn) { ", ar.$ArticleFamilyColumn AS AR_FamilleCode" } else { "" }

$toutesLesLignes = Invoke-SqlQuery $conn @"
SELECT l.DO_Piece, l.AR_Ref, l.DL_Design, l.DL_Qte, l.DL_QteBL, l.DL_DateBL, l.DL_PieceBL,
       l.DL_PrixUnitaire, l.DL_Ligne
       $ligneExtraSelect
       $articleSelect
FROM F_DOCLIGNE l
$articleJoin
WHERE l.DO_Domaine = 1 AND l.DO_Type = 12
ORDER BY l.DO_Piece, l.DL_Ligne ASC
"@

$lignesParPiece = @{}
foreach ($ligne in $toutesLesLignes) {
    $cle = Get-SafeTrim $ligne.DO_Piece
    if (-not $cle) { continue }
    if (-not $lignesParPiece.ContainsKey($cle)) { $lignesParPiece[$cle] = @() }
    $lignesParPiece[$cle] += $ligne
}

$maxSuccessfulModification = $watermark
$firstErrorModification = $null
$successCount = 0
$errorCount = 0
$skippedCount = 0

foreach ($entete in $entetes) {
    # Toute la commande est traitee dans un try/catch : une commande mal formee
    # (champ NULL, valeur inattendue...) ne doit jamais interrompre le traitement
    # des autres commandes du lot.
    try {
        $piece = Get-SafeTrim $entete.DO_Piece
        if (-not $piece) {
            Write-Log "  Entete sans DO_Piece exploitable, ignoree." "WARN"
            $skippedCount++
            continue
        }

        Write-Log "--- Traitement $piece ---"

        $lignes = $lignesParPiece[$piece]

        if (-not $lignes -or $lignes.Count -eq 0) {
            Write-Log "  Aucune ligne trouvee pour $piece, ignoree." "WARN"
            $skippedCount++
            continue
        }

        $tiers = Get-SafeTrim $entete.DO_Tiers
        if (-not $tiers) {
            Write-Log "  Tiers (fournisseur) manquant pour $piece, ignoree." "WARN"
            $skippedCount++
            continue
        }

        $payload = @{
            numero       = $piece
            date         = $entete.DO_Date.ToString("yyyy-MM-dd")
            tiers        = $tiers
            tiers_nom    = Get-SafeTrim $entete.CT_Intitule
            tiers_adresse   = Get-SafeTrim $entete.CT_Adresse
            tiers_ville     = Get-SafeTrim $entete.CT_Ville
            tiers_telephone = Get-SafeTrim $entete.CT_Telephone
            tiers_email     = Get-SafeTrim $entete.CT_Email
            tiers_siret     = Get-SafeTrim $entete.CT_Siret
            montant_ht   = Get-SafeDouble $entete.DO_TotalHT
            montant_ttc  = Get-SafeDouble $entete.DO_TotalTTC
            cloture      = ($entete.DO_Cloture -isnot [System.DBNull] -and [int]$entete.DO_Cloture -eq 1)
            do_souche    = if ($entete.DO_Souche -isnot [System.DBNull]) { [int]$entete.DO_Souche } else { 0 }
            projet_code  = if ($ProjectCodeColumn) { Get-SafeNullableTrim $entete.DO_ProjetCode } else { $null }
            lignes       = @(
                foreach ($ligne in $lignes) {
                    $article = Get-SafeTrim $ligne.AR_Ref
                    if (-not $article) { continue }
                    $dateLivraison = $null
                    if ($ligne.DL_DateBL -isnot [System.DBNull] -and $ligne.DL_DateBL -gt [datetime]"1900-01-01") {
                        $dateLivraison = $ligne.DL_DateBL.ToString("yyyy-MM-dd")
                    }
                    $qte          = Get-SafeDouble $ligne.DL_Qte
                    $prixUnitaire = Get-SafeDouble $ligne.DL_PrixUnitaire
                    $montantHt    = Get-SafeNullableDouble (Get-OptionalColumn $ligne 'DL_MontantHT')
                    $montantTtc   = Get-SafeNullableDouble (Get-OptionalColumn $ligne 'DL_MontantTTC')

                    @{
                        article          = $article
                        designation      = Get-SafeTrim $ligne.DL_Design
                        famille_article  = Get-SafeNullableTrim (Get-OptionalColumn $ligne 'AR_FamilleCode')
                        quantite         = $qte
                        prix_unitaire    = $prixUnitaire
                        taux_tva         = Get-LigneTauxTva $montantHt $montantTtc
                        remise           = Get-LigneRemise $montantHt $qte $prixUnitaire
                        unite            = Get-SafeNullableTrim (Get-OptionalColumn $ligne 'DL_UniteVente')
                        quantite_livree  = Get-SafeDouble $ligne.DL_QteBL
                        date_livraison   = $dateLivraison
                        piece_bl         = Get-SafeTrim $ligne.DL_PieceBL
                    }
                }
            )
        }

        if ($payload.lignes.Count -eq 0) {
            Write-Log "  Aucune ligne avec article valide pour $piece, ignoree." "WARN"
            $skippedCount++
            continue
        }

        # Demandeur reel (F_COLLABORATEUR via CO_No) : si absent, le webhook rattache
        # la commande au compte systeme Sage100 par defaut.
        if ($entete.CO_No -isnot [System.DBNull] -and $entete.CO_No) {
            $nomComplet = ((Get-SafeTrim $entete.CO_Nom) + " " + (Get-SafeTrim $entete.CO_Prenom)).Trim()
            $payload["demandeur"] = @{
                code  = [string]$entete.CO_No
                nom   = $nomComplet
                email = Get-SafeTrim $entete.CO_EMail
            }
        }

        $json = $payload | ConvertTo-Json -Depth 5

        if ($DryRun) {
            Write-Log "  [DRY-RUN] Payload qui serait envoye :`n$json"
            if ($entete.cbModification -gt $maxSuccessfulModification) {
                $maxSuccessfulModification = $entete.cbModification
            }
            $successCount++
            continue
        }

        # PowerShell 5.1 encode -Body en texte avec le codepage systeme par defaut, ce qui
        # corrompt les caracteres accentues (e, e, ...) lors de l'envoi. On force l'UTF-8
        # en convertissant la chaine JSON en tableau d'octets avant l'envoi.
        $utf8Bytes = [System.Text.Encoding]::UTF8.GetBytes($json)

        # Retry sur erreur de connexion (serveur sature par l'envoi en rafale) : jusqu'a
        # 3 tentatives avec pause croissante avant d'abandonner cette commande.
        $maxAttempts = 3
        $attempt = 0
        $sent = $false
        $lastError = $null

        while (-not $sent -and $attempt -lt $maxAttempts) {
            $attempt++
            try {
                $response = Invoke-RestMethod -Uri "$ApiBaseUrl/api/sage/purchase-orders" `
                    -Method Post `
                    -Headers @{ "X-API-Key" = $ApiToken } `
                    -ContentType "application/json; charset=utf-8" `
                    -Body $utf8Bytes `
                    -TimeoutSec 60

                Write-Log "  OK -> id=$($response.id) statut=$($response.status)"
                $successCount++
                if ($entete.cbModification -gt $maxSuccessfulModification) {
                    $maxSuccessfulModification = $entete.cbModification
                }
                $sent = $true
            } catch {
                $lastError = $_
                $statusCode = $null
                if ($_.Exception.Response) { $statusCode = [int]$_.Exception.Response.StatusCode }

                # Une vraie erreur HTTP du serveur (422, 409...) ne se reglera pas en retentant : on abandonne direct.
                if ($statusCode) {
                    break
                }

                if ($attempt -lt $maxAttempts) {
                    Write-Log "  Tentative $attempt/$maxAttempts echouee pour $piece (connexion), nouvel essai dans $($attempt * 2)s..." "WARN"
                    Start-Sleep -Seconds ($attempt * 2)
                }
            }
        }

        if (-not $sent) {
            $statusCode = $null
            if ($lastError.Exception.Response) { $statusCode = [int]$lastError.Exception.Response.StatusCode }
            Write-Log "  ECHEC (HTTP $statusCode) apres $attempt tentative(s) : $($lastError.Exception.Message)" "ERROR"
            $errorCount++
            if (-not $firstErrorModification -or $entete.cbModification -lt $firstErrorModification) {
                $firstErrorModification = $entete.cbModification
            }
        }

        # Petite pause entre chaque commande pour ne pas saturer le serveur (PHP-FPM,
        # envoi d'email synchrone aux validateurs...) sur un gros volume.
        Start-Sleep -Milliseconds 300
    } catch {
        $statusCode = $null
        if ($_.Exception.Response) { $statusCode = [int]$_.Exception.Response.StatusCode }
        Write-Log "  ECHEC (HTTP $statusCode) : $($_.Exception.Message)" "ERROR"
        $errorCount++
        if (-not $firstErrorModification -or $entete.cbModification -lt $firstErrorModification) {
            $firstErrorModification = $entete.cbModification
        }
    }
}

$conn.Close()

if (-not $DryRun) {
    # Le watermark ne doit jamais depasser la premiere vraie erreur HTTP rencontree,
    # sinon les pieces en echec ne seraient plus jamais retentees au passage suivant
    # (le filtre cbModification > @watermark les sauterait definitivement).
    $finalWatermark = $maxSuccessfulModification
    if ($firstErrorModification -and $firstErrorModification -lt $finalWatermark) {
        $finalWatermark = $firstErrorModification.AddSeconds(-1)
    }

    Set-Watermark $finalWatermark
    Write-Log "Watermark avance a : $($finalWatermark.ToString('o'))"
}

Write-Log "=== Fin synchro : $successCount succes, $errorCount echec(s), $skippedCount ignoree(s) (donnees incompletes) ==="
if ($errorCount -gt 0) { exit 2 }
