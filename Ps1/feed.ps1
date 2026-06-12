param(
    [string]$SqlServer = "194.182.91.29\SQLEXPRESS",
    [string]$Database = "JasDb",
    [bool]$UseIntegratedSecurity = $false,
    [string]$DbUser = "sa",
    [string]$DbPassword = "Perft1535",
    [switch]$Refresh = $true
)

$urlB2B = "https://www.koupelny-jas.cz/produkty.xml?authorization=gy2h8fcktm2tivuc8zxbf7eaoggufw1q"
$xmlFileB2B = Join-Path $PSScriptRoot "produkty_b2b.xml"

$urlB2C = "https://www.koupelny-jas.cz/produkty.xml"
$xmlFileB2C = Join-Path $PSScriptRoot "produkty_b2c.xml"

$urlZbozi = "https://www.koupelny-jas.cz/eshop/zbozi-cz.xml"
$xmlFileZbozi = Join-Path $PSScriptRoot "zbozi-cz.xml"

if ($UseIntegratedSecurity) {
    $connectionString = "Server=$SqlServer;Database=$Database;Integrated Security=True;TrustServerCertificate=True;"
}
else {
    $connectionString = "Server=$SqlServer;Database=$Database;User ID=$DbUser;Password=$DbPassword;TrustServerCertificate=True;"
}

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

if ($Refresh -or -not (Test-Path $xmlFileB2B)) {
    Write-Host "Stahuji B2B XML..."
    Invoke-WebRequest -Uri $urlB2B -OutFile $xmlFileB2B -UseBasicParsing
    Write-Host "B2B XML ulozeno: $xmlFileB2B"
}
else {
    Write-Host "Pouzivam existujici B2B XML: $xmlFileB2B"
}

Write-Host "Stahuji B2C XML..."
Invoke-WebRequest -Uri $urlB2C -OutFile $xmlFileB2C -UseBasicParsing
Write-Host "B2C XML ulozeno: $xmlFileB2C"

Write-Host "Stahuji Zbozi.cz XML..."
Invoke-WebRequest -Uri $urlZbozi -OutFile $xmlFileZbozi -UseBasicParsing
Write-Host "Zbozi.cz XML ulozeno: $xmlFileZbozi"

Write-Host "Nacitam B2B XML..."
$content = Get-Content $xmlFileB2B -Encoding UTF8 -Raw
$content = [regex]::Replace(
    $content,
    '[\x00-\x08\x0B\x0C\x0E-\x1F]',
    ''
)
[xml]$xml = $content

$produktColumns = @(
    "index","katalogove_cislo","cena_bez_dph","cena","skladove_mnozstvi",
    "skupina","podskupina","nazev","text","rozmer","barva","povrch","druh",
    "sirka","vyska","hloubka","material","hmotnost","otevirani","system_zavirani",
    "vybaveni","material_polic","uchytky","osvetleni","zaruka","roztec",
    "delka_hadice","delka_raminka","material_kartuse","prumer_kartuse",
    "trida_prutoku","vypln","tloustka_vyplne","provedeni","profil",
    "ochranna_vrstva_skla","tvar","prepad","objem","odpad","splachovani",
    "sedatko","baterie","barevne_provedeni_cela",
    "jednotka","velikost_baleni","velikost_palety","kusu_v_baleni",
    "rektifikace","protiskluz","oteruvzdornost","mrazuvzdornost","tloustka"
)

function Exec-Sql {
    param($Connection, [string]$Sql)

    $cmd = $Connection.CreateCommand()
    $cmd.CommandTimeout = 0
    $cmd.CommandText = $Sql
    $cmd.ExecuteNonQuery() | Out-Null
}

function Get-SqlDataTable {
    param($Connection, [string]$Sql)

    $cmd = $Connection.CreateCommand()
    $cmd.CommandTimeout = 0
    $cmd.CommandText = $Sql

    $dt = New-Object System.Data.DataTable
    $reader = $cmd.ExecuteReader()
    $dt.Load($reader)
    $reader.Close()

    return ,$dt
}

# Nacte z DB mapu: nazev_sloupce -> @{ NetType; SqlType; MaxLen }
function Get-TableTypeMap {
    param($Connection, [string]$TableName)

    $sql = @"
SELECT
    c.name                          AS column_name,
    t.name                          AS sql_type,
    CASE
        WHEN t.name IN ('nvarchar','nchar') AND c.max_length > 0 THEN c.max_length / 2
        WHEN t.name IN ('varchar','char')   AND c.max_length > 0 THEN c.max_length
        WHEN c.max_length = -1              THEN -1
        ELSE c.max_length
    END                             AS char_max_len
FROM sys.columns c
JOIN sys.types   t ON c.user_type_id = t.user_type_id
WHERE c.object_id = OBJECT_ID('$TableName');
"@
    $dt = Get-SqlDataTable $Connection $sql

    $map = @{}
    foreach ($r in $dt.Rows) {
        $col    = [string]$r["column_name"]
        $sqlTyp = [string]$r["sql_type"]
        $maxLen = [int]$r["char_max_len"]

        $netType = switch ($sqlTyp) {
            { $_ -in 'int'      }                           { [int]     ; break }
            { $_ -in 'bigint'   }                           { [long]    ; break }
            { $_ -in 'smallint','tinyint' }                 { [int]     ; break }
            { $_ -in 'bit'      }                           { [bool]    ; break }
            { $_ -in 'decimal','numeric','money','smallmoney' } { [decimal] ; break }
            { $_ -in 'float'    }                           { [double]  ; break }
            { $_ -in 'real'     }                           { [float]   ; break }
            { $_ -in 'date','datetime','datetime2','smalldatetime' } { [datetime]; break }
            default                                         { [string]  }
        }

        $map[$col] = @{ NetType = $netType; SqlType = $sqlTyp; MaxLen = $maxLen }
    }
    return $map
}

# Prevede XML string na .NET typ dle SQL schema; pri chybe vrati DBNull
function Convert-XmlValue {
    param([string]$Value, $ColInfo)

    if ($null -eq $Value -or $Value -eq '') { return [DBNull]::Value }

    $t = $ColInfo.NetType
    try {
        if ($t -eq [string])   { return $Value }
        if ($t -eq [bool])     { return ($Value -eq '1' -or $Value -eq 'true') }
        if ($t -eq [decimal])  { return [decimal]::Parse($Value, [System.Globalization.CultureInfo]::InvariantCulture) }
        if ($t -eq [double])   { return [double]::Parse($Value,  [System.Globalization.CultureInfo]::InvariantCulture) }
        if ($t -eq [float])    { return [float]::Parse($Value,   [System.Globalization.CultureInfo]::InvariantCulture) }
        if ($t -eq [int])      { return [int]::Parse($Value,     [System.Globalization.CultureInfo]::InvariantCulture) }
        if ($t -eq [long])     { return [long]::Parse($Value,    [System.Globalization.CultureInfo]::InvariantCulture) }
        if ($t -eq [datetime]) { return [datetime]::Parse($Value,[System.Globalization.CultureInfo]::InvariantCulture) }
    }
    catch {
        Write-Warning ("Convert-XmlValue: nelze prevest '$Value' na $($t.Name) - ulozen jako NULL")
        return [DBNull]::Value
    }
    return $Value
}

$defaultColInfo = @{ NetType = [string]; SqlType = 'nvarchar'; MaxLen = -1 }

function Get-ColInfo {
    param($Map, [string]$Key)
    if ($Map.ContainsKey($Key)) { return $Map[$Key] }
    return $defaultColInfo
}

function BulkCopyTable {
    param(
        $Connection,
        [string]$TableName,
        [System.Data.DataTable]$DataTable
    )

    Write-Host "Importuji $TableName ($($DataTable.Rows.Count) radku)..."

    $bulk = New-Object System.Data.SqlClient.SqlBulkCopy($Connection)
    $bulk.DestinationTableName = $TableName
    $bulk.BatchSize = 5000
    $bulk.BulkCopyTimeout = 0

    foreach ($column in $DataTable.Columns) {
        [void]$bulk.ColumnMappings.Add($column.ColumnName, $column.ColumnName)
    }

    try {
        $bulk.WriteToServer($DataTable)
    }
    finally {
        $bulk.Close()
    }
}

$conn = New-Object System.Data.SqlClient.SqlConnection($connectionString)

try {
    $conn.Open()
    Write-Host "Pripojeno k SQL Serveru"

    Exec-Sql $conn @"
TRUNCATE TABLE dbo.feed_produkty_obrazky;
TRUNCATE TABLE dbo.feed_produkty_pouziti;
TRUNCATE TABLE dbo.feed_produkty_serie;
TRUNCATE TABLE dbo.feed_produkty;
"@

    Write-Host "Nacitam schema tabulky dbo.feed_produkty z DB..."
    $schemaMap = Get-TableTypeMap $conn 'dbo.feed_produkty'

    if ($schemaMap.Count -eq 0) {
        Write-Host "VAROVANI: Tabulka dbo.feed_produkty nebyla nalezena nebo neobsahuje sloupce. Kontrola preskocena." -ForegroundColor Yellow
    }
    else {
        Write-Host "Kontroluji delky XML proti SQL tabulce..."
        $hasProblem = $false

        foreach ($col in $produktColumns) {
            if (-not $schemaMap.ContainsKey($col)) {
                Write-Host "CHYBI SQL SLOUPEC: $col" -ForegroundColor Red
                $hasProblem = $true
                continue
            }

            $colInfo = $schemaMap[$col]
            # Delku kontrolujeme jen pro string sloupce
            if ($colInfo.NetType -ne [string]) { continue }

            $maxLen   = 0
            $maxIndex = ''
            foreach ($p in $xml.produkty.produkt) {
                if ($p.Attributes[$col]) {
                    $len = $p.Attributes[$col].Value.Length
                    if ($len -gt $maxLen) { $maxLen = $len; $maxIndex = $p.index }
                }
            }

            $sqlLen = $colInfo.MaxLen
            if ($sqlLen -ne -1 -and $maxLen -gt $sqlLen) {
                Write-Host ("KRATKY SLOUPEC: {0} XML={1} SQL={2} index={3}" -f $col, $maxLen, $sqlLen, $maxIndex) -ForegroundColor Red
                $hasProblem = $true
            }
        }

        if ($hasProblem) { throw "SQL tabulka dbo.feed_produkty neodpovida XML." }
        Write-Host "Kontrola OK."
    }

    $dtProdukty = New-Object System.Data.DataTable
    foreach ($c in $produktColumns) {
        $colType = if ($schemaMap.ContainsKey($c)) { $schemaMap[$c].NetType } else { [string] }
        [void]$dtProdukty.Columns.Add($c, $colType)
    }

    $dtPouziti = New-Object System.Data.DataTable
    @("produkt_index", "klic", "nazev") | ForEach-Object {
        [void]$dtPouziti.Columns.Add($_, [string])
    }

    $dtSerie = New-Object System.Data.DataTable
    @("produkt_index", "klic", "nazev") | ForEach-Object {
        [void]$dtSerie.Columns.Add($_, [string])
    }

    $dtObrazky = New-Object System.Data.DataTable
    [void]$dtObrazky.Columns.Add("produkt_index", [string])
    [void]$dtObrazky.Columns.Add("hlavni", [bool])
    [void]$dtObrazky.Columns.Add("url", [string])

    $total = $xml.produkty.produkt.Count
    $current = 0

    foreach ($p in $xml.produkty.produkt) {
        $current++

        if (($current % 500) -eq 0) {
            Write-Host ("Pripravuji data: {0}/{1}" -f $current, $total)
        }

        $row = $dtProdukty.NewRow()

        foreach ($col in $produktColumns) {
            if ($p.Attributes[$col]) {
                $colInfo = if ($schemaMap.ContainsKey($col)) { $schemaMap[$col] } else { @{ NetType = [string]; SqlType = 'nvarchar'; MaxLen = -1 } }
                $row[$col] = Convert-XmlValue $p.Attributes[$col].Value $colInfo
            }
            else {
                $row[$col] = [DBNull]::Value
            }
        }

        [void]$dtProdukty.Rows.Add($row)

        foreach ($u in $p.pouziti) {
            $r = $dtPouziti.NewRow()
            $r["produkt_index"] = $p.index
            $r["klic"] = $u.klic
            $r["nazev"] = $u.nazev
            [void]$dtPouziti.Rows.Add($r)
        }

        foreach ($s in $p.serie) {
            $r = $dtSerie.NewRow()
            $r["produkt_index"] = $p.index
            $r["klic"] = $s.klic
            $r["nazev"] = $s.nazev
            [void]$dtSerie.Rows.Add($r)
        }

        foreach ($o in $p.obrazek) {
            $r = $dtObrazky.NewRow()
            $r["produkt_index"] = $p.index
            $r["hlavni"] = ($o.hlavni -eq "true")
            $r["url"] = $o.url
            [void]$dtObrazky.Rows.Add($r)
        }
    }

    BulkCopyTable $conn "dbo.feed_produkty" $dtProdukty
    BulkCopyTable $conn "dbo.feed_produkty_pouziti" $dtPouziti
    BulkCopyTable $conn "dbo.feed_produkty_serie" $dtSerie
    BulkCopyTable $conn "dbo.feed_produkty_obrazky" $dtObrazky

    # -------------------------------------------------------
    # Zbozi.cz XML
    # -------------------------------------------------------

    Exec-Sql $conn @"
TRUNCATE TABLE dbo.zbozi_shop_depots;
TRUNCATE TABLE dbo.zbozi_categorytext;
TRUNCATE TABLE dbo.zbozi_shopitem;
"@

    Write-Host "Nacitam Zbozi.cz XML..."
    $contentZbozi = Get-Content $xmlFileZbozi -Encoding UTF8 -Raw
    $contentZbozi = [regex]::Replace(
        $contentZbozi,
        '[\x00-\x08\x0B\x0C\x0E-\x1F]',
        ''
    )
    [xml]$xmlZbozi = $contentZbozi

    $nsMgr = New-Object System.Xml.XmlNamespaceManager($xmlZbozi.NameTable)
    $nsMgr.AddNamespace("z", "http://www.zbozi.cz/ns/offer/1.0")

    $shopItems = $xmlZbozi.SelectNodes("//z:SHOPITEM", $nsMgr)

    Write-Host "Nacitam schema tabulky dbo.zbozi_shopitem z DB..."
    $schemaMapZ = Get-TableTypeMap $conn 'dbo.zbozi_shopitem'

    $dtShopItem = New-Object System.Data.DataTable
    @("item_id","productname","description","url","price_vat","manufacturer","imgurl","productno","delivery_date","ean") | ForEach-Object {
        $colType = if ($schemaMapZ.ContainsKey($_)) { $schemaMapZ[$_].NetType } else { [string] }
        [void]$dtShopItem.Columns.Add($_, $colType)
    }

    $dtCategoryText = New-Object System.Data.DataTable
    @("item_id","categorytext") | ForEach-Object {
        [void]$dtCategoryText.Columns.Add($_, [string])
    }

    $dtShopDepots = New-Object System.Data.DataTable
    @("item_id","shop_depot") | ForEach-Object {
        [void]$dtShopDepots.Columns.Add($_, [string])
    }

    $totalZ = $shopItems.Count
    $currentZ = 0

    foreach ($item in $shopItems) {
        $currentZ++
        if (($currentZ % 1000) -eq 0) {
            Write-Host ("Pripravuji Zbozi.cz data: {0}/{1}" -f $currentZ, $totalZ)
        }

        $itemId = $item.SelectSingleNode("z:ITEM_ID", $nsMgr).'#text'

        $row = $dtShopItem.NewRow()
        $row["item_id"]       = Convert-XmlValue $itemId                                                     (Get-ColInfo $schemaMapZ 'item_id')
        $row["productname"]   = Convert-XmlValue ($item.SelectSingleNode('z:PRODUCTNAME',  $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'productname')
        $row["description"]   = Convert-XmlValue ($item.SelectSingleNode('z:DESCRIPTION',  $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'description')
        $row["url"]           = Convert-XmlValue ($item.SelectSingleNode('z:URL',          $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'url')
        $row["price_vat"]     = Convert-XmlValue ($item.SelectSingleNode('z:PRICE_VAT',    $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'price_vat')
        $row["manufacturer"]  = Convert-XmlValue ($item.SelectSingleNode('z:MANUFACTURER', $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'manufacturer')
        $row["imgurl"]        = Convert-XmlValue ($item.SelectSingleNode('z:IMGURL',       $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'imgurl')
        $row["productno"]     = Convert-XmlValue ($item.SelectSingleNode('z:PRODUCTNO',    $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'productno')
        $row["delivery_date"] = Convert-XmlValue ($item.SelectSingleNode('z:DELIVERY_DATE',$nsMgr).'#text') (Get-ColInfo $schemaMapZ 'delivery_date')
        $row["ean"]           = Convert-XmlValue ($item.SelectSingleNode('z:EAN',          $nsMgr).'#text') (Get-ColInfo $schemaMapZ 'ean')
        [void]$dtShopItem.Rows.Add($row)

        foreach ($ct in $item.SelectNodes("z:CATEGORYTEXT", $nsMgr)) {
            $r = $dtCategoryText.NewRow()
            $r["item_id"]      = $itemId
            $r["categorytext"] = $ct.'#text'
            [void]$dtCategoryText.Rows.Add($r)
        }

        foreach ($sd in $item.SelectNodes("z:SHOP_DEPOTS", $nsMgr)) {
            $r = $dtShopDepots.NewRow()
            $r["item_id"]    = $itemId
            $r["shop_depot"] = $sd.'#text'
            [void]$dtShopDepots.Rows.Add($r)
        }
    }

    Exec-Sql $conn @"
TRUNCATE TABLE dbo.zbozi_shop_depots;
TRUNCATE TABLE dbo.zbozi_categorytext;
TRUNCATE TABLE dbo.zbozi_shopitem;
"@

    BulkCopyTable $conn "dbo.zbozi_shopitem"     $dtShopItem
    BulkCopyTable $conn "dbo.zbozi_categorytext" $dtCategoryText
    BulkCopyTable $conn "dbo.zbozi_shop_depots"  $dtShopDepots

    Write-Host ""
    Write-Host "=================================="
    Write-Host "Import dokoncen"
    Write-Host "Produkty : $($dtProdukty.Rows.Count)"
    Write-Host "Pouziti  : $($dtPouziti.Rows.Count)"
    Write-Host "Serie    : $($dtSerie.Rows.Count)"
    Write-Host "Obrazky  : $($dtObrazky.Rows.Count)"
    Write-Host "----------------------------------"
    Write-Host "Zbozi ShopItem   : $($dtShopItem.Rows.Count)"
    Write-Host "Zbozi Category   : $($dtCategoryText.Rows.Count)"
    Write-Host "Zbozi Depots     : $($dtShopDepots.Rows.Count)"
    Write-Host "=================================="
}
finally {
    if ($conn.State -eq "Open") {
        $conn.Close()
    }
}