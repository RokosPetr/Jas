-- Pomocná tabulka: stojany obsažené v konkrétním emailu fronty
-- Spusťte v databázi JasDb

USE JasDb;
GO

IF NOT EXISTS (
	SELECT 1 FROM sys.tables t
	INNER JOIN sys.schemas s ON s.schema_id = t.schema_id
	WHERE s.name = N'dbo' AND t.name = N'EmailQueueStand'
)
BEGIN
	CREATE TABLE dbo.EmailQueueStand
	(
		Id              bigint IDENTITY(1,1) NOT NULL
						CONSTRAINT PK_EmailQueueStand PRIMARY KEY,
		IdEmailQueue    bigint NOT NULL
						CONSTRAINT FK_EmailQueueStand_EmailQueue
						FOREIGN KEY REFERENCES dbo.EmailQueue(Id),
		IdStand         int NOT NULL,
		ChangeDate      date NOT NULL,
		Ico             nvarchar(16) NULL
	);

	CREATE INDEX IX_EmailQueueStand_IdEmailQueue
		ON dbo.EmailQueueStand (IdEmailQueue);

	PRINT N'Tabulka dbo.EmailQueueStand byla vytvořena.';
END
ELSE
BEGIN
	PRINT N'Tabulka dbo.EmailQueueStand již existuje.';
END
GO
