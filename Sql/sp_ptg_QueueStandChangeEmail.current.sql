
CREATE   PROCEDURE dbo.sp_ptg_QueueStandChangeEmail
(
    @ToEmail nvarchar(500) = N'rokos@koupelny-jas.cz',
    @CcEmail nvarchar(500) = NULL,
    @BccEmail nvarchar(500) = NULL,
    @FromDate date = NULL,
    @Subject nvarchar(500) = N'Změny v cenovkách',
    @EmailType int = 1
)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @LastSentAt datetime2(0);
    DECLARE @EffectiveFromDate date;
    DECLARE @Rows int;
    DECLARE @ItemsHtml nvarchar(max);
    DECLARE @Body nvarchar(max);
    DECLARE @StandId int;
    DECLARE @ExistingEmailQueueId int;

    DECLARE @LogoBase64 nvarchar(max) = N'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';

    /*
        Pokud existuje neodeslaný email stejného typu,
        přepíše se místo vložení nového.
    */
    SELECT TOP (1)
        @ExistingEmailQueueId = eq.Id
    FROM dbo.EmailQueue eq
    WHERE eq.EmailType = @EmailType
      AND eq.SentAt IS NULL
      AND ISNULL(eq.Status, N'') NOT IN (N'Sent', N'Cancelled', N'Canceled')
    ORDER BY eq.CreatedAt DESC, eq.Id DESC;

    SELECT
        @LastSentAt = MAX(eq.SentAt)
    FROM dbo.EmailQueue eq
    WHERE eq.EmailType = @EmailType
      AND eq.SentAt IS NOT NULL;

    SET @EffectiveFromDate =
        COALESCE
        (
            @FromDate,
            CAST(@LastSentAt AS date),
            CONVERT(date, '19000101')
        );

    DROP TABLE IF EXISTS #CandidateStands;

    CREATE TABLE #CandidateStands
    (
        IdStand int NOT NULL PRIMARY KEY
    );

    /*
        Rychlý předvýběr kandidátních stojanů.
        Přesné vyhodnocení udělá view, ale pouze s WHERE IdStand = @StandId.
    */

    INSERT INTO #CandidateStands (IdStand)
    SELECT DISTINCT h.id_pt_stand
    FROM JasPdfDb.dbo.pdf_pt_stand_history h
    WHERE h.id_pt_stand IS NOT NULL
      AND h.change_date >= DATEADD(day, 1, @EffectiveFromDate);

    INSERT INTO #CandidateStands (IdStand)
    SELECT DISTINCT p.id_pt_stand
    FROM JasPdfDb.dbo.pdf_pt_plate_item_history h
    INNER JOIN JasPdfDb.dbo.pdf_pt_plate_item i
        ON i.reg_number = h.reg_number
    INNER JOIN JasPdfDb.dbo.pdf_pt_plate p
        ON p.id = i.id_pt_plate
    WHERE h.change_date >= DATEADD(day, 1, @EffectiveFromDate)
      AND NOT EXISTS
      (
          SELECT 1
          FROM #CandidateStands c
          WHERE c.IdStand = p.id_pt_stand
      );

    INSERT INTO #CandidateStands (IdStand)
    SELECT DISTINCT p.id_pt_stand
    FROM JasPdfDb.dbo.pdf_price_tag_history h
    INNER JOIN JasPdfDb.dbo.pdf_pt_plate_item i
        ON i.reg_number = h.reg_number
    INNER JOIN JasPdfDb.dbo.pdf_pt_plate p
        ON p.id = i.id_pt_plate
    WHERE h.change_date >= DATEADD(day, 1, @EffectiveFromDate)
      AND NOT EXISTS
      (
          SELECT 1
          FROM #CandidateStands c
          WHERE c.IdStand = p.id_pt_stand
      );

    DROP TABLE IF EXISTS #ChangedStands;

    CREATE TABLE #ChangedStands
    (
        IdStand int NOT NULL,
        ChangeDate date NOT NULL,
        StandName nvarchar(500) NULL,
        StandNameShort nvarchar(500) NULL,
        PdfUrl nvarchar(2000) NOT NULL,

        CONSTRAINT PK_ChangedStands PRIMARY KEY
        (
            ChangeDate,
            IdStand
        )
    );

    DECLARE cur_stands CURSOR LOCAL FAST_FORWARD FOR
        SELECT c.IdStand
        FROM #CandidateStands c
        ORDER BY c.IdStand;

    OPEN cur_stands;

    FETCH NEXT FROM cur_stands INTO @StandId;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        INSERT INTO #ChangedStands
        (
            IdStand,
            ChangeDate,
            StandName,
            StandNameShort,
            PdfUrl
        )
        SELECT DISTINCT
            schs.IdStand,
            schs.ChangeDate,
            pts.name AS StandName,

            LTRIM(RTRIM(
                CASE
                    WHEN CHARINDEX(N'-', ISNULL(pts.name, N'')) > 0
                        THEN LEFT(pts.name, CHARINDEX(N'-', pts.name) - 1)
                    ELSE pts.name
                END
            )) AS StandNameShort,

            N'http://ptg.koupelnyprokazdeho.cz/files/'
            + LTRIM(RTRIM(
                CASE
                    WHEN CHARINDEX(N'-', ISNULL(pts.name, N'')) > 0
                        THEN LEFT(pts.name, CHARINDEX(N'-', pts.name) - 1)
                    ELSE pts.name
                END
            ))
            + N'-'
            + CONVERT(nvarchar(20), schs.IdStand)
            + N'-'
            + CONVERT(char(8), schs.ChangeDate, 112)
            + N'-qr-nopics.pdf' AS PdfUrl

        FROM JasMtzDb.dbo.vi_ptg_stand_change_dates schs
        INNER JOIN JasPdfDb.dbo.pdf_pt_stand pts
            ON pts.id = schs.IdStand

        WHERE schs.IdStand = @StandId
          AND schs.ChangeDate > @EffectiveFromDate
          AND ISNULL(schs.ChangeEmail, 0) = 1
          AND NOT EXISTS
          (
              SELECT 1
              FROM #ChangedStands x
              WHERE x.IdStand = schs.IdStand
                AND x.ChangeDate = schs.ChangeDate
          );

        FETCH NEXT FROM cur_stands INTO @StandId;
    END;

    CLOSE cur_stands;
    DEALLOCATE cur_stands;

    SELECT
        @Rows = COUNT(*)
    FROM #ChangedStands;

    IF ISNULL(@Rows, 0) = 0
    BEGIN
        SELECT
            CAST(0 AS bit) AS EmailQueued,
            CAST(NULL AS int) AS EmailQueueId,
            @EffectiveFromDate AS FromDate,
            N'Žádné změny k odeslání.' AS Message;

        RETURN;
    END;

    SELECT
        @ItemsHtml =
        STUFF
        (
            (
                SELECT
                    CONVERT(nvarchar(10), cs.ChangeDate, 120)
                    + N' <a href="'
                    + cs.PdfUrl
                    + N'">'
                    + ISNULL(cs.StandNameShort, N'')
                    + N'</a><br/><br/>'
                FROM #ChangedStands cs
                ORDER BY cs.ChangeDate DESC,
                         cs.StandNameShort ASC,
                         cs.IdStand ASC
                FOR XML PATH(N''), TYPE
            ).value(N'.', N'nvarchar(max)'),
            1,
            0,
            N''
        );

    SET @Body =
        N'<html><body style="font-size: 11.5px;"><p>'
        + N'Dobrý den,<br/><br/>'
        + N'dnešním přeceněním se změnily ceny či jiné parametry cenovek ve stojanech viz níže.<br/><br/>'
        + ISNULL(@ItemsHtml, N'')
        + N'Pro vytištění správných cenovek klikněte na odkazy výše, nebo si stojan podle názvu vyhledejte v '
        + N'<a style="color: red; font-weight: bold;" href="https://www.mamekoupelny.eu/">intranetu</a> '
        + N'v sekci '
        + N'<a style="color: red; font-weight: bold;" href="https://www.mamekoupelny.eu/stock/stand/">stojany</a> '
        + N'a cenovky vytiskněte standardně.<br/><br/>'
        + N'V případě jakýchkoliv nesrovnalostí či komplikací prosím odpovězte na tento e-mail a popište problém.<br/><br/>'
        + N'Děkujeme<br/><br/>'
        + N'<img style="max-width:220px;height:auto;" src="data:image/png;base64,'
        + @LogoBase64
        + N'"/>'
        + N'</p></body></html>';

    IF @ExistingEmailQueueId IS NOT NULL
    BEGIN
        UPDATE dbo.EmailQueue
        SET
            ToEmail = @ToEmail,
            CcEmail = @CcEmail,
            BccEmail = @BccEmail,
            Subject = @Subject,
            Body = @Body,
            IsBodyHtml = 1,
            Status = N'Pending',
            RetryCount = 0,
            MaxRetryCount = 5,
            ScheduledAt = SYSDATETIME(),
            EmailType = @EmailType
        WHERE Id = @ExistingEmailQueueId;

        SELECT
            CAST(1 AS bit) AS EmailQueued,
            @ExistingEmailQueueId AS EmailQueueId,
            @EffectiveFromDate AS FromDate,
            @Rows AS StandChangeCount,
            CAST(1 AS bit) AS ExistingEmailRewritten;
    END
    ELSE
    BEGIN
        INSERT INTO dbo.EmailQueue
        (
            ToEmail,
            CcEmail,
            BccEmail,
            Subject,
            Body,
            IsBodyHtml,
            Status,
            RetryCount,
            MaxRetryCount,
            CreatedAt,
            ScheduledAt,
            EmailType
        )
        VALUES
        (
            @ToEmail,
            @CcEmail,
            @BccEmail,
            @Subject,
            @Body,
            1,
            N'Pending',
            0,
            5,
            SYSDATETIME(),
            SYSDATETIME(),
            @EmailType
        );

        SELECT
            CAST(1 AS bit) AS EmailQueued,
            CONVERT(int, SCOPE_IDENTITY()) AS EmailQueueId,
            @EffectiveFromDate AS FromDate,
            @Rows AS StandChangeCount,
            CAST(0 AS bit) AS ExistingEmailRewritten;
    END;
END
