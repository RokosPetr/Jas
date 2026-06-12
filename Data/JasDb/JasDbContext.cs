using System;
using System.Collections.Generic;
using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasDb;

public partial class JasDbContext : DbContext
{
    public JasDbContext(DbContextOptions<JasDbContext> options)
        : base(options)
    {
    }

    public virtual DbSet<EmailQueue> EmailQueues { get; set; }

    public virtual DbSet<EmailQueueStand> EmailQueueStands { get; set; }

    public virtual DbSet<FeedProdukty> FeedProdukties { get; set; }

    public virtual DbSet<FeedProduktyObrazky> FeedProduktyObrazkies { get; set; }

    public virtual DbSet<FeedProduktyPouziti> FeedProduktyPouzitis { get; set; }

    public virtual DbSet<FeedProduktySerie> FeedProduktySeries { get; set; }

    public virtual DbSet<GnReward> GnRewards { get; set; }

    public virtual DbSet<GnRewardsImportError> GnRewardsImportErrors { get; set; }

    public virtual DbSet<JasDepartment> JasDepartments { get; set; }

    public virtual DbSet<JasProducer> JasProducers { get; set; }

    public virtual DbSet<JasStore> JasStores { get; set; }

    public virtual DbSet<PohCacheMonth> PohCacheMonths { get; set; }

    public virtual DbSet<PohCacheRefreshLog> PohCacheRefreshLogs { get; set; }

    public virtual DbSet<PohSourceMonth> PohSourceMonths { get; set; }

    public virtual DbSet<PohSourceStore> PohSourceStores { get; set; }

    public virtual DbSet<PohVCacheMonth> PohVCacheMonths { get; set; }

    public virtual DbSet<PovSortAccessLog> PovSortAccessLogs { get; set; }

    public virtual DbSet<PovSortItem> PovSortItems { get; set; }

    public virtual DbSet<PovSortItemsImport> PovSortItemsImports { get; set; }

    public virtual DbSet<PovSortItemsImportCsv> PovSortItemsImportCsvs { get; set; }

    public virtual DbSet<PovSortProductsCache> PovSortProductsCaches { get; set; }

    public virtual DbSet<PovSortSummaryCache> PovSortSummaryCaches { get; set; }

    public virtual DbSet<PovSortVersion> PovSortVersions { get; set; }

    public virtual DbSet<PtgCustomer> PtgCustomers { get; set; }

    public virtual DbSet<PtgRabat> PtgRabats { get; set; }

    public virtual DbSet<RabatCisskuText> RabatCisskuTexts { get; set; }

    public virtual DbSet<RabatHodnoty> RabatHodnoties { get; set; }

    public virtual DbSet<RabatPravidla> RabatPravidlas { get; set; }

    public virtual DbSet<RabatTypy> RabatTypies { get; set; }

    public virtual DbSet<RabatVerze> RabatVerzes { get; set; }

    public virtual DbSet<SalesSummaryAgregaceMop> SalesSummaryAgregaceMops { get; set; }

    public virtual DbSet<SalesSummaryNazvyMop> SalesSummaryNazvyMops { get; set; }

    public virtual DbSet<SalesSummaryPivotSouhrn> SalesSummaryPivotSouhrns { get; set; }

    public virtual DbSet<SalesSummarySluzbyMop> SalesSummarySluzbyMops { get; set; }

    public virtual DbSet<UserStoreAccess> UserStoreAccesses { get; set; }

    public virtual DbSet<ViEshopZzasobyStore9RabatEshop> ViEshopZzasobyStore9RabatEshops { get; set; }

    public virtual DbSet<ViK2PalsortObklady> ViK2PalsortObkladies { get; set; }

    public virtual DbSet<ViMopLastReg> ViMopLastRegs { get; set; }

    public virtual DbSet<ViPovSortBaseStock> ViPovSortBaseStocks { get; set; }

    public virtual DbSet<ViPovSortEshopPaletovkaExport> ViPovSortEshopPaletovkaExports { get; set; }

    public virtual DbSet<ViPovSortExportPovinnySortiment> ViPovSortExportPovinnySortiments { get; set; }

    public virtual DbSet<ViPovSortRegNumber> ViPovSortRegNumbers { get; set; }

    public virtual DbSet<ViPtgPriceTag> ViPtgPriceTags { get; set; }

    public virtual DbSet<ViPtgPriceTagActive> ViPtgPriceTagActives { get; set; }

    public virtual DbSet<ViPtgStandChange> ViPtgStandChanges { get; set; }

    public virtual DbSet<ViPtgStandChangeDate> ViPtgStandChangeDates { get; set; }

    public virtual DbSet<VwDashBoardKalendar> VwDashBoardKalendars { get; set; }

    public virtual DbSet<VwDashBoardProdeje> VwDashBoardProdejes { get; set; }

    public virtual DbSet<VwDashBoardProdukty> VwDashBoardProdukties { get; set; }

    public virtual DbSet<VwDashBoardZakaznici> VwDashBoardZakaznicis { get; set; }

    public virtual DbSet<VwRabatAktualni> VwRabatAktualnis { get; set; }

    public virtual DbSet<VwSalesSummaryPivotSouhrn> VwSalesSummaryPivotSouhrns { get; set; }

    public virtual DbSet<VwSalesSummaryPivotZdroj> VwSalesSummaryPivotZdrojs { get; set; }

    public virtual DbSet<VwSalesSummaryPohybK2> VwSalesSummaryPohybK2s { get; set; }

    public virtual DbSet<VwSalesSummaryPohybMop> VwSalesSummaryPohybMops { get; set; }

    public virtual DbSet<VwSalesSummaryProduktMaster> VwSalesSummaryProduktMasters { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.Entity<EmailQueue>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK__EmailQue__3214EC07A35C80E4");

            entity.ToTable("EmailQueue");

            entity.HasIndex(e => new { e.Status, e.ScheduledAt }, "IX_EmailQueue_Status_ScheduledAt");

            entity.Property(e => e.BccEmail).HasMaxLength(500);
            entity.Property(e => e.CcEmail).HasMaxLength(500);
            entity.Property(e => e.CreatedAt)
                .HasPrecision(0)
                .HasDefaultValueSql("(sysdatetime())");
            entity.Property(e => e.IsBodyHtml).HasDefaultValue(true);
            entity.Property(e => e.K2companyId).HasColumnName("K2CompanyId");
            entity.Property(e => e.LockedBy).HasMaxLength(100);
            entity.Property(e => e.MaxRetryCount).HasDefaultValue(5);
            entity.Property(e => e.ProcessingAt).HasPrecision(0);
            entity.Property(e => e.ScheduledAt)
                .HasPrecision(0)
                .HasDefaultValueSql("(sysdatetime())");
            entity.Property(e => e.SentAt).HasPrecision(0);
            entity.Property(e => e.Status)
                .HasMaxLength(20)
                .HasDefaultValue("Pending");
            entity.Property(e => e.Subject).HasMaxLength(500);
            entity.Property(e => e.ToEmail).HasMaxLength(500);
        });

        modelBuilder.Entity<EmailQueueStand>(entity =>
        {
            entity.ToTable("EmailQueueStand");

            entity.HasIndex(e => e.IdEmailQueue, "IX_EmailQueueStand_IdEmailQueue");

            entity.Property(e => e.Ico).HasMaxLength(16);

            entity.HasOne(d => d.IdEmailQueueNavigation).WithMany(p => p.EmailQueueStands)
                .HasForeignKey(d => d.IdEmailQueue)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_EmailQueueStand_EmailQueue");
        });

        modelBuilder.Entity<FeedProdukty>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("feed_produkty");

            entity.Property(e => e.BarevneProvedeniCela).HasColumnName("barevne_provedeni_cela");
            entity.Property(e => e.Barva).HasColumnName("barva");
            entity.Property(e => e.Baterie).HasColumnName("baterie");
            entity.Property(e => e.Cena)
                .HasMaxLength(50)
                .HasColumnName("cena");
            entity.Property(e => e.CenaBezDph)
                .HasMaxLength(50)
                .HasColumnName("cena_bez_dph");
            entity.Property(e => e.DelkaHadice).HasColumnName("delka_hadice");
            entity.Property(e => e.DelkaRaminka).HasColumnName("delka_raminka");
            entity.Property(e => e.Druh).HasColumnName("druh");
            entity.Property(e => e.Hloubka).HasColumnName("hloubka");
            entity.Property(e => e.Hmotnost)
                .HasMaxLength(50)
                .HasColumnName("hmotnost");
            entity.Property(e => e.ImportedAt).HasDefaultValueSql("(sysdatetime())");
            entity.Property(e => e.Index)
                .HasMaxLength(50)
                .HasColumnName("index");
            entity.Property(e => e.KatalogoveCislo)
                .HasMaxLength(50)
                .HasColumnName("katalogove_cislo");
            entity.Property(e => e.Material).HasColumnName("material");
            entity.Property(e => e.MaterialKartuse).HasColumnName("material_kartuse");
            entity.Property(e => e.MaterialPolic).HasColumnName("material_polic");
            entity.Property(e => e.Nazev).HasColumnName("nazev");
            entity.Property(e => e.Objem).HasColumnName("objem");
            entity.Property(e => e.OchrannaVrstvaSkla).HasColumnName("ochranna_vrstva_skla");
            entity.Property(e => e.Odpad).HasColumnName("odpad");
            entity.Property(e => e.Osvetleni).HasColumnName("osvetleni");
            entity.Property(e => e.Otevirani).HasColumnName("otevirani");
            entity.Property(e => e.Podskupina)
                .HasMaxLength(50)
                .HasColumnName("podskupina");
            entity.Property(e => e.Povrch).HasColumnName("povrch");
            entity.Property(e => e.Prepad).HasColumnName("prepad");
            entity.Property(e => e.Profil).HasColumnName("profil");
            entity.Property(e => e.Provedeni).HasColumnName("provedeni");
            entity.Property(e => e.PrumerKartuse).HasColumnName("prumer_kartuse");
            entity.Property(e => e.Rozmer).HasColumnName("rozmer");
            entity.Property(e => e.Roztec).HasColumnName("roztec");
            entity.Property(e => e.Sedatko).HasColumnName("sedatko");
            entity.Property(e => e.Sirka).HasColumnName("sirka");
            entity.Property(e => e.SkladoveMnozstvi)
                .HasMaxLength(50)
                .HasColumnName("skladove_mnozstvi");
            entity.Property(e => e.Skupina)
                .HasMaxLength(50)
                .HasColumnName("skupina");
            entity.Property(e => e.Splachovani).HasColumnName("splachovani");
            entity.Property(e => e.SystemZavirani).HasColumnName("system_zavirani");
            entity.Property(e => e.Text).HasColumnName("text");
            entity.Property(e => e.TloustkaVyplne).HasColumnName("tloustka_vyplne");
            entity.Property(e => e.TridaPrutoku).HasColumnName("trida_prutoku");
            entity.Property(e => e.Tvar).HasColumnName("tvar");
            entity.Property(e => e.Uchytky).HasColumnName("uchytky");
            entity.Property(e => e.Vybaveni).HasColumnName("vybaveni");
            entity.Property(e => e.Vypln).HasColumnName("vypln");
            entity.Property(e => e.Vyska).HasColumnName("vyska");
            entity.Property(e => e.Zaruka).HasColumnName("zaruka");
        });

        modelBuilder.Entity<FeedProduktyObrazky>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("feed_produkty_obrazky");

            entity.Property(e => e.Hlavni).HasColumnName("hlavni");
            entity.Property(e => e.ProduktIndex)
                .HasMaxLength(50)
                .HasColumnName("produkt_index");
            entity.Property(e => e.Url).HasColumnName("url");
        });

        modelBuilder.Entity<FeedProduktyPouziti>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("feed_produkty_pouziti");

            entity.Property(e => e.Klic).HasColumnName("klic");
            entity.Property(e => e.Nazev).HasColumnName("nazev");
            entity.Property(e => e.ProduktIndex)
                .HasMaxLength(50)
                .HasColumnName("produkt_index");
        });

        modelBuilder.Entity<FeedProduktySerie>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("feed_produkty_serie");

            entity.Property(e => e.Klic).HasColumnName("klic");
            entity.Property(e => e.Nazev).HasColumnName("nazev");
            entity.Property(e => e.ProduktIndex)
                .HasMaxLength(100)
                .HasColumnName("produkt_index");
        });

        modelBuilder.Entity<GnReward>(entity =>
        {
            entity.HasKey(e => e.Uid).HasName("PK_GnRewards_1");

            entity.Property(e => e.Uid).HasMaxLength(50);
            entity.Property(e => e.BonusBaseAmount).HasColumnType("decimal(18, 2)");
            entity.Property(e => e.InternalId).HasMaxLength(20);
            entity.Property(e => e.MonthName).HasMaxLength(20);
            entity.Property(e => e.OrderBaseAmount).HasColumnType("decimal(18, 2)");
            entity.Property(e => e.OrderNumber).HasMaxLength(50);
            entity.Property(e => e.PersonName).HasMaxLength(100);
            entity.Property(e => e.Rate).HasColumnType("decimal(10, 4)");
            entity.Property(e => e.RewardAmount).HasColumnType("decimal(18, 2)");
            entity.Property(e => e.RoleName).HasMaxLength(50);
            entity.Property(e => e.StoreName).HasMaxLength(50);
        });

        modelBuilder.Entity<GnRewardsImportError>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK__GnReward__3214EC07346D18D2");

            entity.Property(e => e.BonusBaseAmountRaw).HasMaxLength(200);
            entity.Property(e => e.ErrorDate).HasDefaultValueSql("(sysdatetime())");
            entity.Property(e => e.ErrorReason).HasMaxLength(1000);
            entity.Property(e => e.FilePath).HasMaxLength(4000);
            entity.Property(e => e.GnNumberRaw).HasMaxLength(200);
            entity.Property(e => e.InternalIdRaw).HasMaxLength(200);
            entity.Property(e => e.MonthNameRaw).HasMaxLength(200);
            entity.Property(e => e.OrderBaseAmountRaw).HasMaxLength(200);
            entity.Property(e => e.OrderNumberRaw).HasMaxLength(200);
            entity.Property(e => e.OrderSequenceRaw).HasMaxLength(200);
            entity.Property(e => e.PersonNameRaw).HasMaxLength(300);
            entity.Property(e => e.RateRaw).HasMaxLength(200);
            entity.Property(e => e.RecordDateRaw).HasMaxLength(200);
            entity.Property(e => e.RewardAmountRaw).HasMaxLength(200);
            entity.Property(e => e.RoleNameRaw).HasMaxLength(200);
            entity.Property(e => e.StoreNameRaw).HasMaxLength(200);
            entity.Property(e => e.UidRaw).HasMaxLength(200);
            entity.Property(e => e.YearRaw).HasMaxLength(200);
        });

        modelBuilder.Entity<JasDepartment>(entity =>
        {
            entity.ToTable("jas_department");

            entity.Property(e => e.Id)
                .ValueGeneratedNever()
                .HasColumnName("id");
            entity.Property(e => e.Address)
                .HasMaxLength(255)
                .HasColumnName("address");
            entity.Property(e => e.IdStore).HasColumnName("id_store");
            entity.Property(e => e.Name)
                .HasMaxLength(50)
                .HasColumnName("name");
        });

        modelBuilder.Entity<JasProducer>(entity =>
        {
            entity.ToTable("jas_producer");

            entity.HasIndex(e => e.MopSku, "IX_jas_producer_mop_sku");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Alias)
                .HasMaxLength(50)
                .HasColumnName("alias");
            entity.Property(e => e.FilterGroup).HasColumnName("filter_group");
            entity.Property(e => e.K2Code)
                .HasMaxLength(3)
                .HasColumnName("k2_code");
            entity.Property(e => e.K2Id).HasColumnName("k2_id");
            entity.Property(e => e.LogoImage)
                .HasMaxLength(50)
                .HasColumnName("logo_image");
            entity.Property(e => e.MkId).HasColumnName("mk_id");
            entity.Property(e => e.MopSku).HasColumnName("mop_sku");
            entity.Property(e => e.Name)
                .HasMaxLength(50)
                .HasColumnName("name");
            entity.Property(e => e.PovSortName)
                .HasMaxLength(100)
                .IsUnicode(false);
        });

        modelBuilder.Entity<JasStore>(entity =>
        {
            entity.ToTable("jas_store");

            entity.Property(e => e.Id)
                .ValueGeneratedNever()
                .HasColumnName("id");
            entity.Property(e => e.Name)
                .HasMaxLength(50)
                .HasColumnName("name");
        });

        modelBuilder.Entity<PohCacheMonth>(entity =>
        {
            entity.HasKey(e => new { e.StoreId, e.Rok, e.Mesic });

            entity.ToTable("POH_Cache_Month");

            entity.HasIndex(e => new { e.Rok, e.Mesic }, "IX_POH_Cache_Month_rok_mesic");

            entity.Property(e => e.StoreId).HasColumnName("store_id");
            entity.Property(e => e.Rok).HasColumnName("rok");
            entity.Property(e => e.Mesic).HasColumnName("mesic");
            entity.Property(e => e.CntAll).HasColumnName("cnt_all");
            entity.Property(e => e.CntIn).HasColumnName("cnt_in");
            entity.Property(e => e.CntOut).HasColumnName("cnt_out");
            entity.Property(e => e.LastRefreshAt)
                .HasPrecision(0)
                .HasDefaultValueSql("(sysdatetime())")
                .HasColumnName("last_refresh_at");
            entity.Property(e => e.SourceNote)
                .HasMaxLength(200)
                .HasColumnName("source_note");

            entity.HasOne(d => d.Store).WithMany(p => p.PohCacheMonths)
                .HasForeignKey(d => d.StoreId)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_POH_Cache_Month_store");
        });

        modelBuilder.Entity<PohCacheRefreshLog>(entity =>
        {
            entity.HasKey(e => e.RefreshId);

            entity.ToTable("POH_Cache_RefreshLog");

            entity.HasIndex(e => new { e.StoreId, e.StartedAt }, "IX_POH_Cache_RefreshLog_store_started").IsDescending(false, true);

            entity.Property(e => e.RefreshId).HasColumnName("refresh_id");
            entity.Property(e => e.FinishedAt)
                .HasPrecision(0)
                .HasColumnName("finished_at");
            entity.Property(e => e.M1Month).HasColumnName("m1_month");
            entity.Property(e => e.M1Year).HasColumnName("m1_year");
            entity.Property(e => e.MMonth).HasColumnName("m_month");
            entity.Property(e => e.MYear).HasColumnName("m_year");
            entity.Property(e => e.Message)
                .HasMaxLength(2000)
                .HasColumnName("message");
            entity.Property(e => e.SourcesUsed)
                .HasMaxLength(400)
                .HasColumnName("sources_used");
            entity.Property(e => e.StartedAt)
                .HasPrecision(0)
                .HasDefaultValueSql("(sysdatetime())")
                .HasColumnName("started_at");
            entity.Property(e => e.Status)
                .HasMaxLength(10)
                .IsUnicode(false)
                .HasDefaultValue("STARTED")
                .HasColumnName("status");
            entity.Property(e => e.StoreId).HasColumnName("store_id");

            entity.HasOne(d => d.Store).WithMany(p => p.PohCacheRefreshLogs)
                .HasForeignKey(d => d.StoreId)
                .HasConstraintName("FK_POH_Cache_RefreshLog_store");
        });

        modelBuilder.Entity<PohSourceMonth>(entity =>
        {
            entity.HasKey(e => new { e.StoreId, e.Rok, e.Mesic });

            entity.ToTable("POH_Source_Month");

            entity.HasIndex(e => new { e.ExistsOnSource, e.Rok, e.Mesic }, "IX_POH_Source_Month_exists");

            entity.Property(e => e.StoreId).HasColumnName("store_id");
            entity.Property(e => e.Rok).HasColumnName("rok");
            entity.Property(e => e.Mesic).HasColumnName("mesic");
            entity.Property(e => e.ExistsOnSource).HasColumnName("exists_on_source");
            entity.Property(e => e.LastCheckedAt)
                .HasPrecision(0)
                .HasDefaultValueSql("(sysdatetime())")
                .HasColumnName("last_checked_at");
            entity.Property(e => e.PoTableName)
                .HasMaxLength(128)
                .HasColumnName("po_table_name");

            entity.HasOne(d => d.Store).WithMany(p => p.PohSourceMonths)
                .HasForeignKey(d => d.StoreId)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_POH_Source_Month_store");
        });

        modelBuilder.Entity<PohSourceStore>(entity =>
        {
            entity.HasKey(e => e.StoreId);

            entity.ToTable("POH_Source_Store");

            entity.HasIndex(e => e.IsActive, "IX_POH_Source_Store_active");

            entity.HasIndex(e => e.LinkedServer, "UQ_POH_Source_Store_linked").IsUnique();

            entity.Property(e => e.StoreId)
                .ValueGeneratedNever()
                .HasColumnName("store_id");
            entity.Property(e => e.IsActive)
                .HasDefaultValue(true)
                .HasColumnName("is_active");
            entity.Property(e => e.LinkedServer)
                .HasMaxLength(128)
                .HasColumnName("linked_server");
            entity.Property(e => e.Note)
                .HasMaxLength(200)
                .HasColumnName("note");

            entity.HasOne(d => d.Store).WithOne(p => p.PohSourceStore)
                .HasForeignKey<PohSourceStore>(d => d.StoreId)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_POH_Source_Store_store");
        });

        modelBuilder.Entity<PohVCacheMonth>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("POH_v_Cache_Month");

            entity.Property(e => e.CntAll).HasColumnName("cnt_all");
            entity.Property(e => e.CntIn).HasColumnName("cnt_in");
            entity.Property(e => e.CntOut).HasColumnName("cnt_out");
            entity.Property(e => e.LastRefreshAt)
                .HasPrecision(0)
                .HasColumnName("last_refresh_at");
            entity.Property(e => e.Mesic).HasColumnName("mesic");
            entity.Property(e => e.Rok).HasColumnName("rok");
            entity.Property(e => e.StoreId).HasColumnName("store_id");
            entity.Property(e => e.StoreName)
                .HasMaxLength(50)
                .HasColumnName("store_name");
        });

        modelBuilder.Entity<PovSortAccessLog>(entity =>
        {
            entity.HasKey(e => e.LogId).HasName("PK_TAccessLog");

            entity.ToTable("PovSortAccessLog");

            entity.HasIndex(e => new { e.SqlLogin, e.LogTimeUtc }, "IX_TAccessLog_SqlLogin_LogTimeUtc").IsDescending(false, true);

            entity.Property(e => e.AppName).HasMaxLength(128);
            entity.Property(e => e.HostName).HasMaxLength(128);
            entity.Property(e => e.LogTimeUtc).HasDefaultValueSql("(sysutcdatetime())");
            entity.Property(e => e.SqlLogin).HasMaxLength(128);
            entity.Property(e => e.WorkbookName).HasMaxLength(260);
            entity.Property(e => e.WorkbookPath).HasMaxLength(520);
        });

        modelBuilder.Entity<PovSortItem>(entity =>
        {
            entity.HasKey(e => e.ItemId);

            entity.ToTable("PovSort_Items", tb => tb.HasTrigger("tr_PovSort_Items_LastModifiedAt"));

            entity.HasIndex(e => e.VersionId, "IX_PovSort_Items_VersionId");

            entity.HasIndex(e => new { e.VersionId, e.ProductId }, "UQ_VersionId_ProductId").IsUnique();

            entity.Property(e => e.BuyPrice).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.LastModifiedAt).HasDefaultValueSql("(sysutcdatetime())");
            entity.Property(e => e.MinStockQty).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.PackSize).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.PalletQty).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.ProductId).HasMaxLength(50);

            entity.HasOne(d => d.Version).WithMany(p => p.PovSortItems)
                .HasForeignKey(d => d.VersionId)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_PovSort_Items_Versions");
        });

        modelBuilder.Entity<PovSortItemsImport>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("PovSort_ItemsImport");

            entity.Property(e => e.MinQty).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.MinStockQty).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.ProductId).HasMaxLength(50);
        });

        modelBuilder.Entity<PovSortItemsImportCsv>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("PovSort_ItemsImportCSV");

            entity.Property(e => e.MinQty).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.MinStockQty).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.ProductId).HasMaxLength(50);
        });

        modelBuilder.Entity<PovSortProductsCache>(entity =>
        {
            entity.HasKey(e => e.ProductId);

            entity.ToTable("PovSort_ProductsCache");

            entity.Property(e => e.ProductId).HasMaxLength(50);
            entity.Property(e => e.Brand).HasMaxLength(255);
            entity.Property(e => e.BuyPrice).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.CatalogNo).HasMaxLength(100);
            entity.Property(e => e.CategoryKey).HasMaxLength(255);
            entity.Property(e => e.ManufacturerId).HasMaxLength(50);
            entity.Property(e => e.PackSize).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.PalletQty).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.ProductGroup).HasMaxLength(50);
            entity.Property(e => e.ProductName).HasMaxLength(255);
            entity.Property(e => e.ProductStatus).HasMaxLength(100);
            entity.Property(e => e.Psku)
                .HasMaxLength(50)
                .HasColumnName("PSKU");
            entity.Property(e => e.RefreshedAtUtc).HasDefaultValueSql("(sysutcdatetime())");
            entity.Property(e => e.Reg).HasMaxLength(50);
            entity.Property(e => e.SellPrice).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.Series).HasMaxLength(255);
            entity.Property(e => e.Short1).HasMaxLength(50);
            entity.Property(e => e.Sku)
                .HasMaxLength(50)
                .HasColumnName("SKU");
            entity.Property(e => e.Unit).HasMaxLength(50);
        });

        modelBuilder.Entity<PovSortSummaryCache>(entity =>
        {
            entity.HasKey(e => e.SummaryCacheId);

            entity.ToTable("PovSort_SummaryCache");

            entity.HasIndex(e => e.VersionId, "IX_PovSort_SummaryCache_VersionId");

            entity.HasIndex(e => new { e.VersionId, e.Kategorie, e.Sortiment }, "UX_PovSort_SummaryCache_Version_Kat_Sort").IsUnique();

            entity.Property(e => e.CachedAtUtc).HasDefaultValueSql("(sysutcdatetime())");
            entity.Property(e => e.CachedBy).HasMaxLength(128);
            entity.Property(e => e.Celkem)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("celkem");
            entity.Property(e => e.CelkemKčDekorace)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("celkem Kč dekorace");
            entity.Property(e => e.CelkemKčPlochy)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("celkem Kč plochy");
            entity.Property(e => e.CelkemPalet).HasColumnName("celkem palet");
            entity.Property(e => e.CelkemSerií).HasColumnName("celkem serií");
            entity.Property(e => e.DlažbyPalet).HasColumnName("dlažby palet");
            entity.Property(e => e.Kategorie).HasMaxLength(255);
            entity.Property(e => e.KoupelnyPalet).HasColumnName("koupelny palet");
            entity.Property(e => e.Sortiment).HasMaxLength(255);

            entity.HasOne(d => d.Version).WithMany(p => p.PovSortSummaryCaches)
                .HasForeignKey(d => d.VersionId)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_PovSort_SummaryCache_Versions");
        });

        modelBuilder.Entity<PovSortVersion>(entity =>
        {
            entity.HasKey(e => e.VersionId);

            entity.ToTable("PovSort_Versions", tb => tb.HasTrigger("tr_PovSort_Versions_LastModifiedAt"));

            entity.HasIndex(e => e.VersionStatus, "UX_PovSort_Versions_OneDraft")
                .IsUnique()
                .HasFilter("([VersionStatus]=N'DRAFT')");

            entity.Property(e => e.CreatedAt).HasDefaultValueSql("(sysutcdatetime())");
            entity.Property(e => e.CreatedBy).HasMaxLength(100);
            entity.Property(e => e.FinalizedBy).HasMaxLength(100);
            entity.Property(e => e.LastModifiedAt).HasDefaultValueSql("(sysutcdatetime())");
            entity.Property(e => e.Note).HasMaxLength(255);
            entity.Property(e => e.VersionStatus).HasMaxLength(10);
        });

        modelBuilder.Entity<PtgCustomer>(entity =>
        {
            entity.HasKey(e => e.Login);

            entity.ToTable("ptg_customer");

            entity.Property(e => e.Login)
                .HasMaxLength(100)
                .HasColumnName("login");
            entity.Property(e => e.Branch)
                .HasMaxLength(50)
                .HasColumnName("branch");
            entity.Property(e => e.Cipa).HasColumnName("cipa");
            entity.Property(e => e.City)
                .HasMaxLength(255)
                .HasColumnName("city");
            entity.Property(e => e.CompanyName)
                .HasMaxLength(255)
                .HasColumnName("company_name");
            entity.Property(e => e.EmailCustomer)
                .HasMaxLength(255)
                .HasColumnName("email_customer");
            entity.Property(e => e.EmailSales)
                .HasMaxLength(255)
                .HasColumnName("email_sales");
            entity.Property(e => e.FirstName)
                .HasMaxLength(100)
                .HasColumnName("first_name");
            entity.Property(e => e.Ico)
                .HasColumnType("decimal(18, 0)")
                .HasColumnName("ico");
            entity.Property(e => e.K2CustomerId).HasColumnName("k2_customer_id");
            entity.Property(e => e.K2PriceGroupId).HasColumnName("k2_price_group_id");
            entity.Property(e => e.LastName)
                .HasMaxLength(100)
                .HasColumnName("last_name");
            entity.Property(e => e.NedodanePolozky).HasColumnName("nedodane_polozky");
            entity.Property(e => e.Objednavky).HasColumnName("objednavky");
            entity.Property(e => e.Paleta).HasColumnName("paleta");
            entity.Property(e => e.Password)
                .HasMaxLength(255)
                .HasColumnName("password");
            entity.Property(e => e.Phone)
                .HasMaxLength(50)
                .HasColumnName("phone");
            entity.Property(e => e.RabSkp)
                .HasMaxLength(50)
                .HasColumnName("rab_skp");
            entity.Property(e => e.ShortName)
                .HasMaxLength(255)
                .HasColumnName("short_name");
            entity.Property(e => e.Sklad).HasColumnName("sklad");
            entity.Property(e => e.SkladVlastniPobocky).HasColumnName("sklad_vlastni_pobocky");
            entity.Property(e => e.SkladVsech).HasColumnName("sklad_vsech");
            entity.Property(e => e.Skp)
                .HasMaxLength(50)
                .HasColumnName("skp");
            entity.Property(e => e.Street)
                .HasMaxLength(255)
                .HasColumnName("street");
            entity.Property(e => e.Voj).HasColumnName("voj");
            entity.Property(e => e.Zip)
                .HasMaxLength(30)
                .HasColumnName("zip");
        });

        modelBuilder.Entity<PtgRabat>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("ptg_rabat");

            entity.Property(e => e.Psku).HasColumnName("psku");
            entity.Property(e => e.Rabat).HasColumnName("rabat");
            entity.Property(e => e.Sku).HasColumnName("sku");
        });

        modelBuilder.Entity<RabatCisskuText>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK__rabat_ci__3213E83F69B92BB6");

            entity.ToTable("rabat_cissku_text");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.FGroup).HasColumnName("f_group");
            entity.Property(e => e.K2)
                .HasMaxLength(3)
                .HasColumnName("k2");
            entity.Property(e => e.PopisPodskupiny)
                .HasMaxLength(255)
                .HasColumnName("popis_podskupiny");
            entity.Property(e => e.Poradi).HasColumnName("poradi");
            entity.Property(e => e.Psku)
                .HasMaxLength(50)
                .HasColumnName("psku");
            entity.Property(e => e.Sku).HasColumnName("sku");
        });

        modelBuilder.Entity<RabatHodnoty>(entity =>
        {
            entity.HasKey(e => e.IdHodnoty);

            entity.ToTable("rabat_hodnoty");

            entity.HasIndex(e => e.IdPravidla, "IX_rabat_hodnoty_pravidlo");

            entity.HasIndex(e => e.KodRabatu, "IX_rabat_hodnoty_typ");

            entity.HasIndex(e => new { e.IdPravidla, e.KodRabatu }, "UQ_rabat_hodnoty_pravidlo_typ").IsUnique();

            entity.Property(e => e.IdHodnoty).HasColumnName("id_hodnoty");
            entity.Property(e => e.IdPravidla).HasColumnName("id_pravidla");
            entity.Property(e => e.KodRabatu)
                .HasMaxLength(50)
                .HasColumnName("kod_rabatu");
            entity.Property(e => e.Poznamka).HasColumnName("poznamka");
            entity.Property(e => e.Rabat)
                .HasColumnType("decimal(9, 2)")
                .HasColumnName("rabat");

            entity.HasOne(d => d.IdPravidlaNavigation).WithMany(p => p.RabatHodnoties)
                .HasForeignKey(d => d.IdPravidla)
                .HasConstraintName("FK_rabat_hodnoty_pravidla");

            entity.HasOne(d => d.KodRabatuNavigation).WithMany(p => p.RabatHodnoties)
                .HasForeignKey(d => d.KodRabatu)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_rabat_hodnoty_typy");
        });

        modelBuilder.Entity<RabatPravidla>(entity =>
        {
            entity.HasKey(e => e.IdPravidla);

            entity.ToTable("rabat_pravidla");

            entity.HasIndex(e => new { e.Sku, e.Psku }, "IX_rabat_pravidla_sku_psku");

            entity.HasIndex(e => e.IdVerze, "IX_rabat_pravidla_verze");

            entity.HasIndex(e => new { e.IdVerze, e.Sku, e.Psku, e.ProskuK2 }, "UQ_rabat_pravidla_verze_sku_psku_prosku").IsUnique();

            entity.HasIndex(e => new { e.IdVerze, e.Sku, e.Psku }, "UX_rabat_pravidla_verze_sku_psku").IsUnique();

            entity.Property(e => e.IdPravidla).HasColumnName("id_pravidla");
            entity.Property(e => e.IdVerze).HasColumnName("id_verze");
            entity.Property(e => e.ProskuK2)
                .HasMaxLength(50)
                .HasColumnName("prosku_k2");
            entity.Property(e => e.Psku).HasColumnName("psku");
            entity.Property(e => e.Sku).HasColumnName("sku");

            entity.HasOne(d => d.IdVerzeNavigation).WithMany(p => p.RabatPravidlas)
                .HasForeignKey(d => d.IdVerze)
                .HasConstraintName("FK_rabat_pravidla_verze");
        });

        modelBuilder.Entity<RabatTypy>(entity =>
        {
            entity.HasKey(e => e.KodRabatu);

            entity.ToTable("rabat_typy");

            entity.HasIndex(e => new { e.Poradi, e.KodRabatu }, "UQ_rabat_typy_poradi").IsUnique();

            entity.Property(e => e.KodRabatu)
                .HasMaxLength(50)
                .HasColumnName("kod_rabatu");
            entity.Property(e => e.Aktivni)
                .HasDefaultValue(true)
                .HasColumnName("aktivni");
            entity.Property(e => e.ExcelSirka)
                .HasDefaultValue(12)
                .HasColumnName("excel_sirka");
            entity.Property(e => e.Jednotka)
                .HasMaxLength(10)
                .HasColumnName("jednotka");
            entity.Property(e => e.MaxHodnota)
                .HasColumnType("decimal(9, 2)")
                .HasColumnName("max_hodnota");
            entity.Property(e => e.MinHodnota)
                .HasColumnType("decimal(9, 2)")
                .HasColumnName("min_hodnota");
            entity.Property(e => e.Nazev)
                .HasMaxLength(200)
                .HasColumnName("nazev");
            entity.Property(e => e.Poradi).HasColumnName("poradi");
        });

        modelBuilder.Entity<RabatVerze>(entity =>
        {
            entity.HasKey(e => e.IdVerze);

            entity.ToTable("rabat_verze", tb =>
                {
                    tb.HasTrigger("trg_rabat_finalize");
                    tb.HasTrigger("trg_rabat_only_one_draft");
                });

            entity.HasIndex(e => e.DatumOd, "IX_rabat_verze_datum_od").IsDescending();

            entity.HasIndex(e => e.StavVerze, "UX_rabat_verze_one_draft")
                .IsUnique()
                .HasFilter("([stav_verze]='DRAFT')");

            entity.Property(e => e.IdVerze).HasColumnName("id_verze");
            entity.Property(e => e.Autor)
                .HasMaxLength(100)
                .HasColumnName("autor");
            entity.Property(e => e.DatumOd)
                .HasPrecision(0)
                .HasColumnName("datum_od");
            entity.Property(e => e.Poznamka)
                .HasMaxLength(200)
                .HasColumnName("poznamka");
            entity.Property(e => e.StavVerze)
                .HasMaxLength(10)
                .HasDefaultValue("FINAL")
                .HasColumnName("stav_verze");
            entity.Property(e => e.Vytvoreno)
                .HasPrecision(0)
                .HasDefaultValueSql("(sysutcdatetime())")
                .HasColumnName("vytvoreno");
        });

        modelBuilder.Entity<SalesSummaryAgregaceMop>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("SalesSummary_Agregace_MOP");

            entity.Property(e => e.Castka).HasColumnType("decimal(18, 2)");
            entity.Property(e => e.Kategorie).HasMaxLength(50);
            entity.Property(e => e.Mj)
                .HasMaxLength(20)
                .HasColumnName("MJ");
            entity.Property(e => e.Mnozstvi).HasColumnType("decimal(18, 3)");
            entity.Property(e => e.Nazev).HasMaxLength(250);
            entity.Property(e => e.Pobocka).HasMaxLength(100);
            entity.Property(e => e.PobockaFiltr)
                .HasMaxLength(120)
                .HasDefaultValue("");
            entity.Property(e => e.PoradiKategorie).HasDefaultValue(999);
            entity.Property(e => e.PoradiPobocky).HasDefaultValue(999);
            entity.Property(e => e.Reg).HasMaxLength(10);
            entity.Property(e => e.Serie).HasMaxLength(100);
            entity.Property(e => e.StatusZbozi).HasMaxLength(20);
            entity.Property(e => e.Typ).HasMaxLength(20);
            entity.Property(e => e.Vyrobce).HasMaxLength(100);
        });

        modelBuilder.Entity<SalesSummaryNazvyMop>(entity =>
        {
            entity.HasKey(e => e.Reg);

            entity.ToTable("SalesSummary_Nazvy_MOP");

            entity.Property(e => e.Reg).HasMaxLength(10);
            entity.Property(e => e.DlouhyNazev).HasMaxLength(200);
            entity.Property(e => e.Mj)
                .HasMaxLength(10)
                .HasColumnName("MJ");
            entity.Property(e => e.Nazev).HasMaxLength(100);
            entity.Property(e => e.Nazov2).HasMaxLength(100);
            entity.Property(e => e.Regdod).HasMaxLength(50);
        });

        modelBuilder.Entity<SalesSummaryPivotSouhrn>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("SalesSummary_Pivot_Souhrn");

            entity.Property(e => e.KatalogovéČíslo)
                .HasMaxLength(100)
                .HasColumnName("Katalogové číslo");
            entity.Property(e => e.Kategorie).HasMaxLength(24);
            entity.Property(e => e.KlíčProduktu)
                .HasMaxLength(10)
                .HasColumnName("Klíč produktu");
            entity.Property(e => e.Mj)
                .HasMaxLength(10)
                .HasColumnName("MJ");
            entity.Property(e => e.Množství).HasColumnType("decimal(38, 4)");
            entity.Property(e => e.Název).HasMaxLength(251);
            entity.Property(e => e.Pobočka).HasMaxLength(33);
            entity.Property(e => e.StatusZboží)
                .HasMaxLength(50)
                .HasColumnName("Status zboží");
            entity.Property(e => e.Série).HasMaxLength(100);
            entity.Property(e => e.Typ).HasMaxLength(16);
            entity.Property(e => e.Výrobce).HasMaxLength(50);
            entity.Property(e => e.Částka).HasColumnType("decimal(38, 4)");
        });

        modelBuilder.Entity<SalesSummarySluzbyMop>(entity =>
        {
            entity.HasKey(e => e.Reg);

            entity.ToTable("SalesSummary_Sluzby_MOP");

            entity.Property(e => e.Reg).HasMaxLength(10);
            entity.Property(e => e.DlouhyNazev).HasMaxLength(200);
            entity.Property(e => e.Mj)
                .HasMaxLength(10)
                .HasColumnName("MJ");
            entity.Property(e => e.Nazev).HasMaxLength(100);
            entity.Property(e => e.Psku).HasColumnName("PSKU");
            entity.Property(e => e.Sku).HasColumnName("SKU");
        });

        modelBuilder.Entity<UserStoreAccess>(entity =>
        {
            entity.HasKey(e => e.LoginName).HasName("PK_TUserStoreAccess");

            entity.ToTable("UserStoreAccess");

            entity.Property(e => e.LoginName).HasMaxLength(128);
        });

        modelBuilder.Entity<ViEshopZzasobyStore9RabatEshop>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_eshop_ZzasobyStore9_RabatEshop");

            entity.Property(e => e.DruhRabatu)
                .HasMaxLength(50)
                .HasColumnName("druh rabatu");
            entity.Property(e => e.IdVerze).HasColumnName("id_verze");
            entity.Property(e => e.Index)
                .HasMaxLength(10)
                .HasColumnName("index");
            entity.Property(e => e.KatalogovéČíslo)
                .HasMaxLength(50)
                .HasColumnName("katalogové číslo");
            entity.Property(e => e.Mj)
                .HasMaxLength(5)
                .HasColumnName("mj");
            entity.Property(e => e.Název)
                .HasMaxLength(50)
                .HasColumnName("název");
            entity.Property(e => e.PopisPodskupiny)
                .HasMaxLength(50)
                .HasColumnName("popis podskupiny");
            entity.Property(e => e.Poznámka).HasColumnName("poznámka");
            entity.Property(e => e.Prec)
                .HasColumnType("decimal(18, 3)")
                .HasColumnName("prec");
            entity.Property(e => e.Psku).HasColumnName("PSKU");
            entity.Property(e => e.Rabat)
                .HasColumnType("decimal(9, 2)")
                .HasColumnName("rabat");
            entity.Property(e => e.Sku).HasColumnName("SKU");
            entity.Property(e => e.Série)
                .HasMaxLength(50)
                .HasColumnName("série");
        });

        modelBuilder.Entity<ViK2PalsortObklady>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_k2_palsort_obklady");

            entity.Property(e => e.IdVyrobce)
                .HasMaxLength(3)
                .HasColumnName("id_vyrobce");
            entity.Property(e => e.KatalogoveCislo)
                .HasMaxLength(100)
                .HasColumnName("katalogove_cislo");
            entity.Property(e => e.Mj)
                .HasMaxLength(6)
                .HasColumnName("mj");
            entity.Property(e => e.MopIndex)
                .HasMaxLength(30)
                .HasColumnName("mop_index");
            entity.Property(e => e.NadrizenyKlic)
                .HasMaxLength(100)
                .HasColumnName("nadrizeny_klic");
            entity.Property(e => e.Nazev)
                .HasMaxLength(200)
                .HasColumnName("nazev");
            entity.Property(e => e.Serie)
                .HasMaxLength(100)
                .HasColumnName("serie");
            entity.Property(e => e.Skupina)
                .HasMaxLength(50)
                .HasColumnName("skupina");
            entity.Property(e => e.StatusProduktu)
                .HasMaxLength(50)
                .HasColumnName("status_produktu");
            entity.Property(e => e.Vyrobce)
                .HasMaxLength(50)
                .HasColumnName("vyrobce");
            entity.Property(e => e.Zasoba)
                .HasColumnType("decimal(11, 2)")
                .HasColumnName("zasoba");
            entity.Property(e => e.Zkratka)
                .HasMaxLength(30)
                .HasColumnName("zkratka");
        });

        modelBuilder.Entity<ViMopLastReg>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_mop_last_reg");

            entity.Property(e => e.Reg).HasColumnName("reg");
            entity.Property(e => e.Sku).HasColumnName("sku");
        });

        modelBuilder.Entity<ViPovSortBaseStock>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_PovSort_BaseStock");

            entity.Property(e => e.Dlnaz)
                .HasMaxLength(50)
                .HasColumnName("dlnaz");
            entity.Property(e => e.Dopl)
                .HasMaxLength(1)
                .HasColumnName("dopl");
            entity.Property(e => e.Jkpov)
                .HasMaxLength(50)
                .HasColumnName("jkpov");
            entity.Property(e => e.Mj)
                .HasMaxLength(5)
                .HasColumnName("mj");
            entity.Property(e => e.Nakc)
                .HasColumnType("decimal(18, 3)")
                .HasColumnName("nakc");
            entity.Property(e => e.Nazov)
                .HasMaxLength(50)
                .HasColumnName("nazov");
            entity.Property(e => e.Nazov2)
                .HasMaxLength(50)
                .HasColumnName("nazov2");
            entity.Property(e => e.Prec)
                .HasColumnType("decimal(18, 3)")
                .HasColumnName("prec");
            entity.Property(e => e.Psku).HasColumnName("psku");
            entity.Property(e => e.Reg)
                .HasMaxLength(10)
                .HasColumnName("reg");
            entity.Property(e => e.Regdod)
                .HasMaxLength(50)
                .HasColumnName("regdod");
            entity.Property(e => e.Sektor)
                .HasMaxLength(50)
                .HasColumnName("sektor");
            entity.Property(e => e.Sku).HasColumnName("sku");
            entity.Property(e => e.Store).HasColumnName("store");
            entity.Property(e => e.Vaha)
                .HasColumnType("decimal(18, 3)")
                .HasColumnName("vaha");
            entity.Property(e => e.Variant)
                .HasMaxLength(50)
                .HasColumnName("variant");
            entity.Property(e => e.Vbal)
                .HasColumnType("decimal(18, 3)")
                .HasColumnName("vbal");
            entity.Property(e => e.Zasoba)
                .HasColumnType("decimal(18, 3)")
                .HasColumnName("zasoba");
        });

        modelBuilder.Entity<ViPovSortEshopPaletovkaExport>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_PovSort_EshopPaletovkaExport");

            entity.Property(e => e.Index).HasMaxLength(50);
            entity.Property(e => e.MinOdběr)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("Min odběr");
            entity.Property(e => e.PaletovkaMo).HasColumnName("Paletovka MO");
            entity.Property(e => e.PaletovéMnožství)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("Paletové množství");
            entity.Property(e => e.Vbal)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("vbal");
        });

        modelBuilder.Entity<ViPovSortExportPovinnySortiment>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_PovSort_ExportPovinnySortiment");

            entity.Property(e => e.IndexMop)
                .HasMaxLength(50)
                .HasColumnName("Index MOP");
            entity.Property(e => e.Kategorie).HasMaxLength(255);
            entity.Property(e => e.KoeficientMj)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("Koeficient MJ");
            entity.Property(e => e.Minimum).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.Mj)
                .HasMaxLength(50)
                .HasColumnName("MJ");
            entity.Property(e => e.MnožstvíProExport)
                .HasColumnType("decimal(18, 4)")
                .HasColumnName("Množství pro export");
            entity.Property(e => e.Název).HasMaxLength(255);
            entity.Property(e => e.ProductId).HasMaxLength(50);
            entity.Property(e => e.Série).HasMaxLength(255);
            entity.Property(e => e.Výrobce).HasMaxLength(50);
        });

        modelBuilder.Entity<ViPovSortRegNumber>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_PovSort_regNumber");

            entity.Property(e => e.RegNumber)
                .HasMaxLength(15)
                .HasColumnName("reg_number");
        });

        modelBuilder.Entity<ViPtgPriceTag>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_price_tag");

            entity.Property(e => e.Abrasion)
                .HasMaxLength(5)
                .HasColumnName("abrasion");
            entity.Property(e => e.Antislip)
                .HasMaxLength(5)
                .HasColumnName("antislip");
            entity.Property(e => e.Description)
                .HasMaxLength(255)
                .HasColumnName("description");
            entity.Property(e => e.Discarded).HasColumnName("discarded");
            entity.Property(e => e.Discount).HasColumnName("discount");
            entity.Property(e => e.Frost).HasColumnName("frost");
            entity.Property(e => e.ItemId).HasColumnName("item_id");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.OrigName)
                .HasMaxLength(255)
                .HasColumnName("orig_name");
            entity.Property(e => e.Outlet).HasColumnName("outlet");
            entity.Property(e => e.OutletQr)
                .HasMaxLength(255)
                .HasColumnName("outlet_qr");
            entity.Property(e => e.PlateId).HasColumnName("plate_id");
            entity.Property(e => e.Price).HasColumnName("price");
            entity.Property(e => e.PriceJas).HasColumnName("price_jas");
            entity.Property(e => e.PriceNn).HasColumnName("price_nn");
            entity.Property(e => e.Qr)
                .HasMaxLength(255)
                .HasColumnName("qr");
            entity.Property(e => e.Rectification).HasColumnName("rectification");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");
            entity.Property(e => e.Size)
                .HasMaxLength(50)
                .HasColumnName("size");
            entity.Property(e => e.StandId).HasColumnName("stand_id");
            entity.Property(e => e.Surface)
                .HasMaxLength(3)
                .HasColumnName("surface");
            entity.Property(e => e.TypeOrder).HasColumnName("type_order");
            entity.Property(e => e.Unit)
                .HasMaxLength(50)
                .HasColumnName("unit");
        });

        modelBuilder.Entity<ViPtgPriceTagActive>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_price_tag_active");

            entity.Property(e => e.Abrasion)
                .HasMaxLength(5)
                .HasColumnName("abrasion");
            entity.Property(e => e.Antislip)
                .HasMaxLength(5)
                .HasColumnName("antislip");
            entity.Property(e => e.Description)
                .HasMaxLength(255)
                .HasColumnName("description");
            entity.Property(e => e.Discarded).HasColumnName("discarded");
            entity.Property(e => e.Discount).HasColumnName("discount");
            entity.Property(e => e.Frost).HasColumnName("frost");
            entity.Property(e => e.ItemId).HasColumnName("item_id");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.OrigName)
                .HasMaxLength(255)
                .HasColumnName("orig_name");
            entity.Property(e => e.Outlet).HasColumnName("outlet");
            entity.Property(e => e.OutletQr)
                .HasMaxLength(255)
                .HasColumnName("outlet_qr");
            entity.Property(e => e.PlateId).HasColumnName("plate_id");
            entity.Property(e => e.Price).HasColumnName("price");
            entity.Property(e => e.PriceJas).HasColumnName("price_jas");
            entity.Property(e => e.PriceNn).HasColumnName("price_nn");
            entity.Property(e => e.Qr)
                .HasMaxLength(255)
                .HasColumnName("qr");
            entity.Property(e => e.Rectification).HasColumnName("rectification");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");
            entity.Property(e => e.Size)
                .HasMaxLength(50)
                .HasColumnName("size");
            entity.Property(e => e.StandId).HasColumnName("stand_id");
            entity.Property(e => e.StandMkId).HasColumnName("stand_mk_id");
            entity.Property(e => e.StandType).HasColumnName("stand_type");
            entity.Property(e => e.Surface)
                .HasMaxLength(3)
                .HasColumnName("surface");
            entity.Property(e => e.TypeOrder).HasColumnName("type_order");
            entity.Property(e => e.Unit)
                .HasMaxLength(50)
                .HasColumnName("unit");
        });

        modelBuilder.Entity<ViPtgStandChange>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_stand_change");

            entity.Property(e => e.ChangeDateAbrasion)
                .HasColumnType("datetime")
                .HasColumnName("change_date_abrasion");
            entity.Property(e => e.ChangeDateAntislip)
                .HasColumnType("datetime")
                .HasColumnName("change_date_antislip");
            entity.Property(e => e.ChangeDateDescription)
                .HasColumnType("datetime")
                .HasColumnName("change_date_description");
            entity.Property(e => e.ChangeDateDiscarded)
                .HasColumnType("datetime")
                .HasColumnName("change_date_discarded");
            entity.Property(e => e.ChangeDateDiscount)
                .HasColumnType("datetime")
                .HasColumnName("change_date_discount");
            entity.Property(e => e.ChangeDateFrost)
                .HasColumnType("datetime")
                .HasColumnName("change_date_frost");
            entity.Property(e => e.ChangeDateInsert)
                .HasColumnType("datetime")
                .HasColumnName("change_date_insert");
            entity.Property(e => e.ChangeDateName)
                .HasColumnType("datetime")
                .HasColumnName("change_date_name");
            entity.Property(e => e.ChangeDateOrigName)
                .HasColumnType("datetime")
                .HasColumnName("change_date_orig_name");
            entity.Property(e => e.ChangeDateOutlet)
                .HasColumnType("datetime")
                .HasColumnName("change_date_outlet");
            entity.Property(e => e.ChangeDateOutletQr)
                .HasColumnType("datetime")
                .HasColumnName("change_date_outlet_qr");
            entity.Property(e => e.ChangeDatePrice)
                .HasColumnType("datetime")
                .HasColumnName("change_date_price");
            entity.Property(e => e.ChangeDatePriceJas)
                .HasColumnType("datetime")
                .HasColumnName("change_date_price_jas");
            entity.Property(e => e.ChangeDatePriceNn)
                .HasColumnType("datetime")
                .HasColumnName("change_date_price_nn");
            entity.Property(e => e.ChangeDateQr)
                .HasColumnType("datetime")
                .HasColumnName("change_date_qr");
            entity.Property(e => e.ChangeDateRectification)
                .HasColumnType("datetime")
                .HasColumnName("change_date_rectification");
            entity.Property(e => e.ChangeDateSize)
                .HasColumnType("datetime")
                .HasColumnName("change_date_size");
            entity.Property(e => e.ChangeDateSurface)
                .HasColumnType("datetime")
                .HasColumnName("change_date_surface");
            entity.Property(e => e.ChangeDateUnit)
                .HasColumnType("datetime")
                .HasColumnName("change_date_unit");
            entity.Property(e => e.StandId).HasColumnName("stand_id");
        });

        modelBuilder.Entity<ViPtgStandChangeDate>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_stand_change_dates");
        });

        modelBuilder.Entity<VwDashBoardKalendar>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_DashBoard_Kalendar");

            entity.Property(e => e.Date).HasColumnType("datetime");
            entity.Property(e => e.DayName).HasMaxLength(30);
            entity.Property(e => e.MonthName).HasMaxLength(4000);
            entity.Property(e => e.MonthNameShort).HasMaxLength(4000);
        });

        modelBuilder.Entity<VwDashBoardProdeje>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_DashBoard_Prodeje");

            entity.Property(e => e.AmountNet).HasColumnType("decimal(38, 4)");
            entity.Property(e => e.Branch).HasMaxLength(50);
            entity.Property(e => e.CompanyRegNumber).HasMaxLength(50);
            entity.Property(e => e.CustomerAbbr).HasMaxLength(255);
            entity.Property(e => e.CustomerName).HasMaxLength(50);
            entity.Property(e => e.Margin).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.Quantity).HasColumnType("decimal(22, 4)");
            entity.Property(e => e.Reg).HasMaxLength(50);
            entity.Property(e => e.Source)
                .HasMaxLength(3)
                .IsUnicode(false);
        });

        modelBuilder.Entity<VwDashBoardProdukty>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_DashBoard_Produkty");

            entity.Property(e => e.Category).HasMaxLength(19);
            entity.Property(e => e.K2abbr)
                .HasMaxLength(30)
                .HasColumnName("K2Abbr");
            entity.Property(e => e.Producer).HasMaxLength(50);
            entity.Property(e => e.ProductName).HasMaxLength(200);
            entity.Property(e => e.Psku).HasColumnName("PSku");
            entity.Property(e => e.Reg).HasMaxLength(50);
            entity.Property(e => e.Series).HasMaxLength(100);
            entity.Property(e => e.Unit).HasMaxLength(6);
        });

        modelBuilder.Entity<VwDashBoardZakaznici>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_DashBoard_Zakaznici");

            entity.Property(e => e.Branch).HasMaxLength(50);
            entity.Property(e => e.CompanyRegNumber).HasMaxLength(30);
            entity.Property(e => e.CustomerAbbr).HasMaxLength(50);
            entity.Property(e => e.CustomerName).HasMaxLength(200);
            entity.Property(e => e.PostalCode).HasMaxLength(30);
            entity.Property(e => e.Source)
                .HasMaxLength(3)
                .IsUnicode(false);
        });

        modelBuilder.Entity<VwRabatAktualni>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_rabat_aktualni");

            entity.Property(e => e.DatumOd)
                .HasPrecision(0)
                .HasColumnName("datum_od");
            entity.Property(e => e.IdVerze).HasColumnName("id_verze");
            entity.Property(e => e.Jednotka)
                .HasMaxLength(10)
                .HasColumnName("jednotka");
            entity.Property(e => e.KodRabatu)
                .HasMaxLength(50)
                .HasColumnName("kod_rabatu");
            entity.Property(e => e.NazevRabatu)
                .HasMaxLength(200)
                .HasColumnName("nazev_rabatu");
            entity.Property(e => e.PopisPodskupiny)
                .HasMaxLength(50)
                .HasColumnName("popis_podskupiny");
            entity.Property(e => e.Poznamka).HasColumnName("poznamka");
            entity.Property(e => e.ProskuK2)
                .HasMaxLength(50)
                .HasColumnName("prosku_k2");
            entity.Property(e => e.Psku).HasColumnName("psku");
            entity.Property(e => e.Rabat)
                .HasColumnType("decimal(9, 2)")
                .HasColumnName("rabat");
            entity.Property(e => e.Sku).HasColumnName("sku");
        });

        modelBuilder.Entity<VwSalesSummaryPivotSouhrn>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_SalesSummary_Pivot_Souhrn");

            entity.Property(e => e.KatalogovéČíslo)
                .HasMaxLength(100)
                .HasColumnName("Katalogové číslo");
            entity.Property(e => e.Kategorie).HasMaxLength(24);
            entity.Property(e => e.KlíčProduktu)
                .HasMaxLength(10)
                .HasColumnName("Klíč produktu");
            entity.Property(e => e.Mj)
                .HasMaxLength(10)
                .HasColumnName("MJ");
            entity.Property(e => e.Množství).HasColumnType("decimal(38, 4)");
            entity.Property(e => e.Název).HasMaxLength(251);
            entity.Property(e => e.Pobočka).HasMaxLength(33);
            entity.Property(e => e.StatusZboží)
                .HasMaxLength(50)
                .HasColumnName("Status zboží");
            entity.Property(e => e.Série).HasMaxLength(100);
            entity.Property(e => e.Typ).HasMaxLength(16);
            entity.Property(e => e.Výrobce).HasMaxLength(50);
            entity.Property(e => e.Částka).HasColumnType("decimal(38, 4)");
        });

        modelBuilder.Entity<VwSalesSummaryPivotZdroj>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_SalesSummary_Pivot_Zdroj");

            entity.Property(e => e.Abbr2).HasMaxLength(100);
            entity.Property(e => e.Castka).HasColumnType("decimal(38, 4)");
            entity.Property(e => e.Kategorie).HasMaxLength(50);
            entity.Property(e => e.Mj)
                .HasMaxLength(10)
                .HasColumnName("MJ");
            entity.Property(e => e.Mnozstvi).HasColumnType("decimal(31, 4)");
            entity.Property(e => e.Nazev).HasMaxLength(251);
            entity.Property(e => e.Pobocka).HasMaxLength(50);
            entity.Property(e => e.PobockaFiltr).HasMaxLength(55);
            entity.Property(e => e.Reg).HasMaxLength(10);
            entity.Property(e => e.Serie).HasMaxLength(100);
            entity.Property(e => e.StatusZbozi).HasMaxLength(50);
            entity.Property(e => e.Typ).HasMaxLength(16);
            entity.Property(e => e.Vyrobce).HasMaxLength(50);
            entity.Property(e => e.Zdroj).HasMaxLength(3);
        });

        modelBuilder.Entity<VwSalesSummaryPohybK2>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_SalesSummary_Pohyb_K2");

            entity.Property(e => e.Abbr).HasMaxLength(50);
            entity.Property(e => e.Abbr2).HasMaxLength(50);
            entity.Property(e => e.Castka).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.CisloDokladu).HasMaxLength(50);
            entity.Property(e => e.Kategorie).HasMaxLength(19);
            entity.Property(e => e.Mj)
                .HasMaxLength(6)
                .HasColumnName("MJ");
            entity.Property(e => e.Mnozstvi).HasColumnType("decimal(18, 4)");
            entity.Property(e => e.Nazev).HasMaxLength(200);
            entity.Property(e => e.Pobocka).HasMaxLength(50);
            entity.Property(e => e.PobockaFiltr).HasMaxLength(50);
            entity.Property(e => e.Regdod).HasMaxLength(100);
            entity.Property(e => e.Serie).HasMaxLength(100);
            entity.Property(e => e.StatusZbozi).HasMaxLength(50);
            entity.Property(e => e.Typ).HasMaxLength(16);
            entity.Property(e => e.Vyrobce).HasMaxLength(50);
        });

        modelBuilder.Entity<VwSalesSummaryPohybMop>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_SalesSummary_Pohyb_MOP");

            entity.Property(e => e.Castka).HasColumnType("decimal(38, 4)");
            entity.Property(e => e.Dopl).HasMaxLength(1);
            entity.Property(e => e.Kategorie).HasMaxLength(50);
            entity.Property(e => e.Mj)
                .HasMaxLength(10)
                .HasColumnName("MJ");
            entity.Property(e => e.Mnozstvi).HasColumnType("decimal(29, 2)");
            entity.Property(e => e.Nazev).HasMaxLength(251);
            entity.Property(e => e.Pobocka).HasMaxLength(50);
            entity.Property(e => e.PobockaFiltr).HasMaxLength(55);
            entity.Property(e => e.Psku).HasColumnName("PSKU");
            entity.Property(e => e.Reg).HasMaxLength(10);
            entity.Property(e => e.Regdod).HasMaxLength(50);
            entity.Property(e => e.Serie).HasMaxLength(100);
            entity.Property(e => e.Sku).HasColumnName("SKU");
            entity.Property(e => e.StatusZbozi).HasMaxLength(14);
            entity.Property(e => e.Typ).HasMaxLength(10);
            entity.Property(e => e.Vyrobce).HasMaxLength(50);
        });

        modelBuilder.Entity<VwSalesSummaryProduktMaster>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vw_SalesSummary_Produkt_Master");

            entity.Property(e => e.Abbr).HasMaxLength(50);
            entity.Property(e => e.Abbr2).HasMaxLength(50);
            entity.Property(e => e.JeVk2).HasColumnName("JeVK2");
            entity.Property(e => e.JeVmop).HasColumnName("JeVMOP");
            entity.Property(e => e.Kategorie).HasMaxLength(19);
            entity.Property(e => e.KlicProduktu).HasMaxLength(10);
            entity.Property(e => e.Mj)
                .HasMaxLength(10)
                .HasColumnName("MJ");
            entity.Property(e => e.Nazev).HasMaxLength(251);
            entity.Property(e => e.Reg).HasMaxLength(10);
            entity.Property(e => e.Regdod).HasMaxLength(100);
            entity.Property(e => e.Serie).HasMaxLength(100);
            entity.Property(e => e.StatusZbozi).HasMaxLength(50);
            entity.Property(e => e.Typ).HasMaxLength(16);
            entity.Property(e => e.Vyrobce).HasMaxLength(50);
        });

        OnModelCreatingPartial(modelBuilder);
    }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder);
}
