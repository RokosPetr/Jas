-- Tabulky pro import XML produktového feedu
-- Spusťte v databázi JasDb

USE JasDb;
GO

IF OBJECT_ID('dbo.feed_produkty_obrazky', 'U') IS NOT NULL DROP TABLE dbo.feed_produkty_obrazky;
IF OBJECT_ID('dbo.feed_produkty_pouziti', 'U') IS NOT NULL DROP TABLE dbo.feed_produkty_pouziti;
IF OBJECT_ID('dbo.feed_produkty_serie',   'U') IS NOT NULL DROP TABLE dbo.feed_produkty_serie;
IF OBJECT_ID('dbo.feed_produkty',         'U') IS NOT NULL DROP TABLE dbo.feed_produkty;
GO

CREATE TABLE dbo.feed_produkty (
	[index]               nvarchar(100) NULL,
	katalogove_cislo      nvarchar(max) NULL,
	cena_bez_dph          nvarchar(max) NULL,
	cena                  nvarchar(max) NULL,
	skladove_mnozstvi     nvarchar(max) NULL,
	skupina               nvarchar(max) NULL,
	podskupina            nvarchar(max) NULL,
	nazev                 nvarchar(max) NULL,
	[text]                nvarchar(max) NULL,
	rozmer                nvarchar(max) NULL,
	barva                 nvarchar(max) NULL,
	povrch                nvarchar(max) NULL,
	druh                  nvarchar(max) NULL,
	sirka                 nvarchar(max) NULL,
	vyska                 nvarchar(max) NULL,
	hloubka               nvarchar(max) NULL,
	material              nvarchar(max) NULL,
	hmotnost              nvarchar(max) NULL,
	otevirani             nvarchar(max) NULL,
	system_zavirani       nvarchar(max) NULL,
	vybaveni              nvarchar(max) NULL,
	material_polic        nvarchar(max) NULL,
	uchytky               nvarchar(max) NULL,
	osvetleni             nvarchar(max) NULL,
	zaruka                nvarchar(max) NULL,
	roztec                nvarchar(max) NULL,
	delka_hadice          nvarchar(max) NULL,
	delka_raminka         nvarchar(max) NULL,
	material_kartuse      nvarchar(max) NULL,
	prumer_kartuse        nvarchar(max) NULL,
	trida_prutoku         nvarchar(max) NULL,
	vypln                 nvarchar(max) NULL,
	tloustka_vyplne       nvarchar(max) NULL,
	provedeni             nvarchar(max) NULL,
	profil                nvarchar(max) NULL,
	ochranna_vrstva_skla  nvarchar(max) NULL,
	tvar                  nvarchar(max) NULL,
	prepad                nvarchar(max) NULL,
	objem                 nvarchar(max) NULL,
	odpad                 nvarchar(max) NULL,
	splachovani           nvarchar(max) NULL,
	sedatko               nvarchar(max) NULL,
	baterie               nvarchar(max) NULL,
	barevne_provedeni_cela nvarchar(max) NULL,
	jednotka              nvarchar(max) NULL,
	velikost_baleni       nvarchar(max) NULL,
	velikost_palety       nvarchar(max) NULL,
	kusu_v_baleni         nvarchar(max) NULL,
	rektifikace           nvarchar(max) NULL,
	protiskluz            nvarchar(max) NULL,
	oteruvzdornost        nvarchar(max) NULL,
	mrazuvzdornost        nvarchar(max) NULL,
	tloustka              nvarchar(max) NULL
);
GO

CREATE TABLE dbo.feed_produkty_pouziti (
	produkt_index nvarchar(100) NULL,
	klic          nvarchar(max) NULL,
	nazev         nvarchar(max) NULL
);
GO

CREATE TABLE dbo.feed_produkty_serie (
	produkt_index nvarchar(100) NULL,
	klic          nvarchar(max) NULL,
	nazev         nvarchar(max) NULL
);
GO

CREATE TABLE dbo.feed_produkty_obrazky (
	produkt_index nvarchar(100) NULL,
	hlavni        bit           NULL,
	url           nvarchar(max) NULL
);
GO

PRINT N'Tabulky feed_produkty, feed_produkty_pouziti, feed_produkty_serie, feed_produkty_obrazky byly vytvoreny.';
GO
