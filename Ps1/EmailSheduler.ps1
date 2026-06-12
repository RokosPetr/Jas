param(
    # SQL Server
    [string]$SqlServer = "localhost\SQLEXPRESS",
    [string]$Database = "JasDb",
    [bool]$UseIntegratedSecurity = $false,
    [string]$DbUser = "sa",
    [string]$DbPassword = "Perft1535",

    # SMTP
    [string]$SmtpHost = "email.active24.com",
    [int]$SmtpPort = 587,
    [bool]$SmtpUseSsl = $true,
    [string]$SmtpUser = "postmaster2@koupelny-jas.cz",
    [string]$SmtpPassword = "E3aCtN6yOWCY",
    [string]$MailFrom = "rokos@koupelny-jas.cz",

    # Worker
    [int]$BatchSize = 10,
    [string]$WorkerName = $env:COMPUTERNAME,

    # Filtr ICO (prazdny = vsechny stojany)
    [string]$Ico = "27792803",

    # Odeslat zmeny od tohoto data bez ohledu na posledni odesilani (prazdny = od posledniho odeslani)
    [Nullable[datetime]]$FromDate = $null,

    # Vyvojovy rezim: email se odesle, ale nezaznamena jako Sent a pristi beh ho prepise
    [bool]$Development = $true
)

$ErrorActionPreference = "Stop"

function Get-StringSafe {
    param($Value)

    if ($null -eq $Value -or $Value -is [System.DBNull]) {
        return ""
    }

    return [string]$Value
}

function Get-BoolSafe {
    param($Value)

    if ($null -eq $Value -or $Value -is [System.DBNull]) {
        return $false
    }

    return [bool]$Value
}

function Get-ConnectionString {
    param(
        [string]$SqlServer,
        [string]$Database,
        [bool]$UseIntegratedSecurity,
        [string]$DbUser,
        [string]$DbPassword
    )

    if ($UseIntegratedSecurity) {
        return "Server=$SqlServer;Database=$Database;Integrated Security=True;TrustServerCertificate=True;"
    }
    else {
        return "Server=$SqlServer;Database=$Database;User ID=$DbUser;Password=$DbPassword;TrustServerCertificate=True;"
    }
}

function Invoke-DbNonQuery {
    param(
        [string]$ConnectionString,
        [string]$Sql,
        [hashtable]$Parameters
    )

    $conn = New-Object System.Data.SqlClient.SqlConnection($ConnectionString)
    $cmd = $conn.CreateCommand()
    $cmd.CommandText = $Sql

    foreach ($key in $Parameters.Keys) {
        $paramValue = $Parameters[$key]
        if ($null -eq $paramValue) {
            $paramValue = [DBNull]::Value
        }
        [void]$cmd.Parameters.AddWithValue($key, $paramValue)
    }

    try {
        $conn.Open()
        [void]$cmd.ExecuteNonQuery()
    }
    finally {
        $cmd.Dispose()
        $conn.Close()
        $conn.Dispose()
    }
}

function Get-QueuedEmails {
    param(
        [string]$ConnectionString,
        [int]$BatchSize,
        [string]$WorkerName
    )

    $conn = New-Object System.Data.SqlClient.SqlConnection($connectionString)
    $cmd = $conn.CreateCommand()
    $cmd.CommandText = "dbo.usp_EmailQueue_ClaimBatch"
    $cmd.CommandType = [System.Data.CommandType]::StoredProcedure

    [void]$cmd.Parameters.AddWithValue("@BatchSize", $BatchSize)
    [void]$cmd.Parameters.AddWithValue("@LockedBy", $WorkerName)

    $table = New-Object System.Data.DataTable

    try {
        $conn.Open()
        $reader = $cmd.ExecuteReader()
        try {
            $table.Load($reader)
        }
        finally {
            $reader.Dispose()
        }

        return $table.Rows
    }
    finally {
        $cmd.Dispose()
        $conn.Close()
        $conn.Dispose()
    }
}

function Add-MailAddresses {
    param(
        [System.Net.Mail.MailAddressCollection]$Collection,
        [string]$Addresses
    )

    if ([string]::IsNullOrWhiteSpace($Addresses)) {
        return
    }

    $Addresses -split '[;,]' | ForEach-Object {
        $addr = $_.Trim()
        if (-not [string]::IsNullOrWhiteSpace($addr)) {
            $Collection.Add($addr)
        }
    }
}

function Send-QueueMail {
    param(
        [string]$SmtpHost,
        [int]$SmtpPort,
        [bool]$SmtpUseSsl,
        [string]$SmtpUser,
        [string]$SmtpPassword,
        [string]$MailFrom,
        $Row
    )

    $toEmail = Get-StringSafe $Row.ToEmail
    $cc = Get-StringSafe $Row.CcEmail
    $bcc = Get-StringSafe $Row.BccEmail
    $subject = Get-StringSafe $Row.Subject
    $body = Get-StringSafe $Row.Body
    $isBodyHtml = Get-BoolSafe $Row.IsBodyHtml

    if ([string]::IsNullOrWhiteSpace($toEmail)) {
        throw "Email ID $($Row.Id) nemá vyplněný ToEmail."
    }

    $message = New-Object System.Net.Mail.MailMessage
    $message.From = $MailFrom
    $message.To.Add($toEmail)

    Add-MailAddresses -Collection $message.CC -Addresses $cc
    Add-MailAddresses -Collection $message.Bcc -Addresses $bcc

    $message.Subject = $subject
    $message.Body = $body
    $message.IsBodyHtml = $isBodyHtml
    $message.SubjectEncoding = [System.Text.Encoding]::UTF8
    $message.BodyEncoding = [System.Text.Encoding]::UTF8

    $smtp = New-Object System.Net.Mail.SmtpClient($SmtpHost, $SmtpPort)
    $smtp.EnableSsl = $SmtpUseSsl

    if (-not [string]::IsNullOrWhiteSpace($SmtpUser) -and -not [string]::IsNullOrWhiteSpace($SmtpPassword)) {
        $smtp.Credentials = New-Object System.Net.NetworkCredential($SmtpUser, $SmtpPassword)
    }
    else {
        $smtp.UseDefaultCredentials = $true
    }

    try {
        $smtp.Send($message)
    }
    finally {
        $message.Dispose()
        $smtp.Dispose()
    }
}

$connectionString = Get-ConnectionString `
    -SqlServer $SqlServer `
    -Database $Database `
    -UseIntegratedSecurity $UseIntegratedSecurity `
    -DbUser $DbUser `
    -DbPassword $DbPassword

try {
    if ($Ico -eq "27792803") {

        Invoke-DbNonQuery `
            -ConnectionString $connectionString `
            -Sql "EXEC dbo.sp_ptg_email_jasprice;" `
            -Parameters @{}

    }
    else {

        Invoke-DbNonQuery `
            -ConnectionString $connectionString `
            -Sql "EXEC dbo.sp_ptg_QueueStandChangeEmail @Ico = @Ico, @FromDate = @FromDate;" `
            -Parameters @{
                "@Ico"      = if ([string]::IsNullOrWhiteSpace($Ico)) { $null } else { $Ico.Trim() }
                "@FromDate" = if ($null -eq $FromDate) { $null } else { $FromDate.ToString("yyyy-MM-dd") }
            }

    }

    $emails = Get-QueuedEmails `
        -ConnectionString $connectionString `
        -BatchSize $BatchSize `
        -WorkerName $WorkerName

    if ($null -eq $emails) {
        Write-Host "Ve frontě nejsou žádné emaily k odeslání."
        return
    }

    if ($emails -isnot [System.Array]) {
        $emails = @($emails)
    }

    if ($emails.Count -eq 0) {
        Write-Host "Ve frontě nejsou žádné emaily k odeslání."
        return
    }

    foreach ($row in $emails) {
        try {
            Send-QueueMail `
                -SmtpHost $SmtpHost `
                -SmtpPort $SmtpPort `
                -SmtpUseSsl $SmtpUseSsl `
                -SmtpUser $SmtpUser `
                -SmtpPassword $SmtpPassword `
                -MailFrom $MailFrom `
                -Row $row

            if ($Development) {
                $sqlDevReset = @"
UPDATE dbo.EmailQueue
SET Status = 'Sent',
    SentAt = SYSDATETIME(),
    LastError = NULL,
    ProcessingAt = NULL,
    LockedBy = NULL
WHERE Id = @Id;
"@
                Invoke-DbNonQuery `
                    -ConnectionString $connectionString `
                    -Sql $sqlDevReset `
                    -Parameters @{ "@Id" = [int64]$row.Id }

                Write-Host "[DEV] Email ID $($row.Id) odeslan na $(Get-StringSafe $row.ToEmail) - oznacen jako Sent (Development=true, pristi beh posle nove zmeny)"
            }
            else {
                $sqlSent = @"
UPDATE dbo.EmailQueue
SET Status = 'Sent',
    SentAt = SYSDATETIME(),
    LastError = NULL,
    ProcessingAt = NULL,
    LockedBy = NULL
WHERE Id = @Id;
"@
                Invoke-DbNonQuery `
                    -ConnectionString $connectionString `
                    -Sql $sqlSent `
                    -Parameters @{ "@Id" = [int64]$row.Id }

                Write-Host "OK - Email ID $($row.Id) byl odeslan na $(Get-StringSafe $row.ToEmail)"
            }
        }
        catch {
            $errorText = $_.Exception.Message

            $sqlFailed = @"
UPDATE dbo.EmailQueue
SET RetryCount = RetryCount + 1,
    LastError = @LastError,
    Status = CASE
                WHEN RetryCount + 1 >= MaxRetryCount THEN 'Failed'
                ELSE 'Pending'
             END,
    ProcessingAt = NULL,
    LockedBy = NULL
WHERE Id = @Id;
"@

            Invoke-DbNonQuery `
                -ConnectionString $connectionString `
                -Sql $sqlFailed `
                -Parameters @{
                    "@Id" = [int64]$row.Id
                    "@LastError" = $errorText
                }

            Write-Warning "CHYBA - Email ID $($row.Id): $errorText"
        }
    }
}
catch {
    Write-Error "Obecná chyba skriptu: $($_.Exception.Message)"
    exit 1
}