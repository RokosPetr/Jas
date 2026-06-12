SET NOCOUNT ON;
GO

DECLARE @Definition nvarchar(max) = OBJECT_DEFINITION(OBJECT_ID(N'dbo.sp_ptg_QueueStandChangeEmail'));

IF @Definition IS NULL
BEGIN
    THROW 50000, N'Procedura dbo.sp_ptg_QueueStandChangeEmail nebyla nalezena.', 1;
END;

IF CHARINDEX(N'@Ico nvarchar(16)', @Definition) = 0
BEGIN
    SET @Definition = REPLACE(
        @Definition,
        N'    @Subject nvarchar(500) = N''Změny v cenovkách'',
    @EmailType int = 1',
        N'    @Subject nvarchar(500) = N''Změny v cenovkách'',
    @EmailType int = 1,
    @Ico nvarchar(16) = NULL'
    );
END;

IF CHARINDEX(N'FROM JasMtzDb.dbo.ptg_stand_company sc', @Definition) = 0
BEGIN
    SET @Definition = REPLACE(
        @Definition,
        N'    DROP TABLE IF EXISTS #ChangedStands;',
        N'    IF NULLIF(LTRIM(RTRIM(@Ico)), N'''') IS NOT NULL
    BEGIN
        DELETE c
        FROM #CandidateStands c
        WHERE NOT EXISTS
        (
            SELECT 1
            FROM JasMtzDb.dbo.ptg_stand_company sc
            WHERE sc.id_stand = c.IdStand
              AND sc.ico = LTRIM(RTRIM(@Ico))
        );
    END;

    DROP TABLE IF EXISTS #ChangedStands;'
    );
END;

IF CHARINDEX(N'REPLACE(REPLACE(LTRIM(RTRIM(', @Definition) = 0
BEGIN
    SET @Definition = REPLACE(
        @Definition,
        N'            LTRIM(RTRIM(
                CASE
                    WHEN CHARINDEX(N''-'', ISNULL(pts.name, N'''')) > 0
                        THEN LEFT(pts.name, CHARINDEX(N''-'', pts.name) - 1)
                    ELSE pts.name
                END
            )) AS StandNameShort,',
        N'            REPLACE(REPLACE(LTRIM(RTRIM(
                CASE
                    WHEN CHARINDEX(N''-'', ISNULL(pts.name, N'''')) > 0
                        THEN LEFT(pts.name, CHARINDEX(N''-'', pts.name) - 1)
                    ELSE pts.name
                END
            )), N''/'', N''-''), NCHAR(92), N''-'') AS StandNameShort,'
    );

    SET @Definition = REPLACE(
        @Definition,
        N'            N''http://ptg.koupelnyprokazdeho.cz/files/''
            + LTRIM(RTRIM(
                CASE
                    WHEN CHARINDEX(N''-'', ISNULL(pts.name, N'''')) > 0
                        THEN LEFT(pts.name, CHARINDEX(N''-'', pts.name) - 1)
                    ELSE pts.name
                END
            ))
            + N''-''',
        N'            N''http://ptg.koupelnyprokazdeho.cz/files/''
            + REPLACE(REPLACE(LTRIM(RTRIM(
                CASE
                    WHEN CHARINDEX(N''-'', ISNULL(pts.name, N'''')) > 0
                        THEN LEFT(pts.name, CHARINDEX(N''-'', pts.name) - 1)
                    ELSE pts.name
                END
            )), N''/'', N''-''), NCHAR(92), N''-'')
            + N''-'''
    );
END;

SET @Definition = REPLACE(
    @Definition,
    N'CREATE   PROCEDURE dbo.sp_ptg_QueueStandChangeEmail',
    N'ALTER PROCEDURE dbo.sp_ptg_QueueStandChangeEmail'
);

SET @Definition = REPLACE(
    @Definition,
    N'CREATE PROCEDURE dbo.sp_ptg_QueueStandChangeEmail',
    N'ALTER PROCEDURE dbo.sp_ptg_QueueStandChangeEmail'
);

EXEC sys.sp_executesql @Definition;
GO

PRINT N'Procedura dbo.sp_ptg_QueueStandChangeEmail byla upravena o parametr @Ico, filtr stojanů přes JasMtzDb.dbo.ptg_stand_company a nahrazení lomítek v názvu stojanu.';
GO
