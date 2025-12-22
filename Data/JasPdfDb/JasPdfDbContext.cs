using System;
using System.Collections.Generic;
using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasPdfDb;

public partial class JasPdfDbContext : DbContext
{
    public JasPdfDbContext(DbContextOptions<JasPdfDbContext> options)
        : base(options)
    {
    }

    public virtual DbSet<JasSeries> JasSeries { get; set; }

    public virtual DbSet<PdfCatalog> PdfCatalogs { get; set; }

    public virtual DbSet<PdfCompany> PdfCompanies { get; set; }

    public virtual DbSet<PdfCompanyCatalog> PdfCompanyCatalogs { get; set; }

    public virtual DbSet<PdfContent> PdfContents { get; set; }

    public virtual DbSet<PdfContentJson> PdfContentJsons { get; set; }

    public virtual DbSet<PdfFile> PdfFiles { get; set; }

    public virtual DbSet<PdfItemPrice> PdfItemPrices { get; set; }

    public virtual DbSet<PdfPriceTag> PdfPriceTags { get; set; }

    public virtual DbSet<PdfPriceTagHistory> PdfPriceTagHistories { get; set; }

    public virtual DbSet<PdfPtEmail> PdfPtEmails { get; set; }

    public virtual DbSet<PdfPtEmailAttachment> PdfPtEmailAttachments { get; set; }

    public virtual DbSet<PdfPtFile> PdfPtFiles { get; set; }

    public virtual DbSet<PdfPtPlate> PdfPtPlates { get; set; }

    public virtual DbSet<PdfPtPlateItem> PdfPtPlateItems { get; set; }

    public virtual DbSet<PdfPtPlateItemHistory> PdfPtPlateItemHistories { get; set; }

    public virtual DbSet<PdfPtStand> PdfPtStands { get; set; }

    public virtual DbSet<PdfPtStandHistory> PdfPtStandHistories { get; set; }

    public virtual DbSet<PdfPtType> PdfPtTypes { get; set; }

    public virtual DbSet<PdfQr> PdfQrs { get; set; }

    public virtual DbSet<PdfRedirect> PdfRedirects { get; set; }

    public virtual DbSet<PdfSlider> PdfSliders { get; set; }

    public virtual DbSet<PdfText> PdfTexts { get; set; }

    public virtual DbSet<Produkty> Produkties { get; set; }

    public virtual DbSet<Rabat> Rabats { get; set; }

    public virtual DbSet<ViPdfCatalogDownload> ViPdfCatalogDownloads { get; set; }

    public virtual DbSet<ViPdfCompanyCatalogFile> ViPdfCompanyCatalogFiles { get; set; }

    public virtual DbSet<ViPdfContent> ViPdfContents { get; set; }

    public virtual DbSet<ViPdfDownload> ViPdfDownloads { get; set; }

    public virtual DbSet<ViPdfFile> ViPdfFiles { get; set; }

    public virtual DbSet<ViPdfItem> ViPdfItems { get; set; }

    public virtual DbSet<ViPdfPagesCount> ViPdfPagesCounts { get; set; }

    public virtual DbSet<ViPdfPriceTag> ViPdfPriceTags { get; set; }

    public virtual DbSet<ViPdfPriceTag1> ViPdfPriceTags1 { get; set; }

    public virtual DbSet<ViPdfPtChangeEmail> ViPdfPtChangeEmails { get; set; }

    public virtual DbSet<ViPdfPtChangedStand> ViPdfPtChangedStands { get; set; }

    public virtual DbSet<ViPdfPtPlate> ViPdfPtPlates { get; set; }

    public virtual DbSet<ViPdfPtPlateBak> ViPdfPtPlateBaks { get; set; }

    public virtual DbSet<ViPdfPtPriceTagHistory> ViPdfPtPriceTagHistories { get; set; }

    public virtual DbSet<ViPdfPtStandHistory> ViPdfPtStandHistories { get; set; }

    public virtual DbSet<ViPdfQr> ViPdfQrs { get; set; }

    public virtual DbSet<ViPdfQrRedirect> ViPdfQrRedirects { get; set; }

    public virtual DbSet<ViPdfRedirect> ViPdfRedirects { get; set; }

    public virtual DbSet<ViPdfSearchText> ViPdfSearchTexts { get; set; }

    public virtual DbSet<ViPdfSlider> ViPdfSliders { get; set; }

    public virtual DbSet<ViPdfText> ViPdfTexts { get; set; }

    public virtual DbSet<View1> View1s { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.Entity<JasSeries>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("jas_series");

            entity.Property(e => e.KlicSerie)
                .HasMaxLength(100)
                .HasColumnName("klicSerie");
            entity.Property(e => e.NazevSerie)
                .HasMaxLength(100)
                .HasColumnName("nazevSerie");
            entity.Property(e => e.NazevVyrobce)
                .HasMaxLength(100)
                .HasColumnName("nazevVyrobce");
        });

        modelBuilder.Entity<PdfCatalog>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK_catalog_file");

            entity.ToTable("pdf_catalog");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.Landscape).HasColumnName("landscape");
            entity.Property(e => e.Title)
                .HasMaxLength(255)
                .HasColumnName("title");
        });

        modelBuilder.Entity<PdfCompany>(entity =>
        {
            entity.ToTable("pdf_company");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.CssStyle)
                .HasMaxLength(255)
                .HasColumnName("css_style");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.Email)
                .HasMaxLength(255)
                .HasColumnName("email");
            entity.Property(e => e.ImgLogo)
                .HasMaxLength(255)
                .HasColumnName("img_logo");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.Phone)
                .HasMaxLength(255)
                .HasColumnName("phone");
            entity.Property(e => e.Url)
                .HasMaxLength(255)
                .HasColumnName("url");
        });

        modelBuilder.Entity<PdfCompanyCatalog>(entity =>
        {
            entity.ToTable("pdf_company_catalog");

            entity.HasIndex(e => new { e.IdCatalog, e.IdCompany, e.IdFile }, "ix_pdf_company_catalog_main");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.BeginIndexFrom).HasColumnName("begin_index_from");
            entity.Property(e => e.BeginIndexTo).HasColumnName("begin_index_to");
            entity.Property(e => e.EndingIndexFrom).HasColumnName("ending_index_from");
            entity.Property(e => e.EndingIndexTo).HasColumnName("ending_index_to");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndexFrom).HasColumnName("page_index_from");
            entity.Property(e => e.PageIndexTo).HasColumnName("page_index_to");
            entity.Property(e => e.PageOrder)
                .HasDefaultValue(1)
                .HasColumnName("page_order");
            entity.Property(e => e.PageStartOn)
                .HasDefaultValue(1)
                .HasColumnName("page_start_on");

            entity.HasOne(d => d.IdCatalogNavigation).WithMany(p => p.PdfCompanyCatalogs)
                .HasForeignKey(d => d.IdCatalog)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_company_catalog_pdf_catalog1");

            entity.HasOne(d => d.IdCompanyNavigation).WithMany(p => p.PdfCompanyCatalogs)
                .HasForeignKey(d => d.IdCompany)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_company_catalog_pdf_company");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfCompanyCatalogs)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_company_catalog_pdf_file");
        });

        modelBuilder.Entity<PdfContent>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK_pdf_catalog_content");

            entity.ToTable("pdf_content");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.ContentLevel).HasColumnName("content_level");
            entity.Property(e => e.ContentOrder).HasColumnName("content_order");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.IdParent).HasColumnName("id_parent");
            entity.Property(e => e.PageIndexFrom).HasColumnName("page_index_from");
            entity.Property(e => e.PageIndexTo).HasColumnName("page_index_to");
            entity.Property(e => e.Title)
                .HasMaxLength(255)
                .HasColumnName("title");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfContents)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_content_pdf_file");
        });

        modelBuilder.Entity<PdfContentJson>(entity =>
        {
            entity.ToTable("pdf_content_json");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.CatalogKey)
                .HasMaxLength(50)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(50)
                .HasColumnName("company_key");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.Json)
                .HasColumnType("ntext")
                .HasColumnName("json");
        });

        modelBuilder.Entity<PdfFile>(entity =>
        {
            entity.ToTable("pdf_file");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.CatalogType)
                .HasMaxLength(50)
                .HasColumnName("catalog_type");
            entity.Property(e => e.ContentGroup)
                .HasMaxLength(50)
                .HasColumnName("content_group");
            entity.Property(e => e.CountPages).HasColumnName("count_pages");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.IdParent).HasColumnName("id_parent");
            entity.Property(e => e.Modified)
                .HasColumnType("datetime")
                .HasColumnName("modified");
            entity.Property(e => e.Path)
                .HasMaxLength(1000)
                .HasColumnName("path");
            entity.Property(e => e.PdfType)
                .HasMaxLength(50)
                .HasColumnName("pdf_type");
            entity.Property(e => e.Series)
                .HasMaxLength(1000)
                .HasColumnName("series");
            entity.Property(e => e.Size).HasColumnName("size");
        });

        modelBuilder.Entity<PdfItemPrice>(entity =>
        {
            entity.HasKey(e => new { e.IdCompany, e.IdFile, e.PageIndex, e.ReplaceCode });

            entity.ToTable("pdf_item_price");

            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.ReplaceCode)
                .HasMaxLength(20)
                .HasColumnName("replace_code");
            entity.Property(e => e.PdfPrice).HasColumnName("pdf_price");
            entity.Property(e => e.Price).HasColumnName("price");
            entity.Property(e => e.Rabat).HasColumnName("rabat");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(20)
                .HasColumnName("reg_number");
            entity.Property(e => e.ReplaceText)
                .HasMaxLength(20)
                .HasColumnName("replace_text");
            entity.Property(e => e.Unit)
                .HasMaxLength(10)
                .HasColumnName("unit");
            entity.Property(e => e.ValidDate)
                .HasColumnType("datetime")
                .HasColumnName("valid_date");
            entity.Property(e => e.X).HasColumnName("x");
            entity.Property(e => e.Y).HasColumnName("y");

            entity.HasOne(d => d.IdCompanyNavigation).WithMany(p => p.PdfItemPrices)
                .HasForeignKey(d => d.IdCompany)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_item_price_pdf_company");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfItemPrices)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_item_price_pdf_file");
        });

        modelBuilder.Entity<PdfPriceTag>(entity =>
        {
            entity.HasKey(e => e.RegNumber).IsClustered(false);

            entity.ToTable("pdf_price_tag", tb =>
                {
                    tb.HasTrigger("trg_AfterInsert_pdf_price_tag");
                    tb.HasTrigger("trg_AfterUpdate_pdf_price_tag");
                });

            entity.HasIndex(e => e.RegNumber, "IX_pdf_price_tag")
                .IsUnique()
                .IsClustered();

            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");
            entity.Property(e => e.Abrasion)
                .HasMaxLength(5)
                .HasColumnName("abrasion");
            entity.Property(e => e.Antislip)
                .HasMaxLength(5)
                .HasColumnName("antislip");
            entity.Property(e => e.Discarded).HasColumnName("discarded");
            entity.Property(e => e.Discount).HasColumnName("discount");
            entity.Property(e => e.Frost).HasColumnName("frost");
            entity.Property(e => e.Inserted).HasColumnName("inserted");
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
            entity.Property(e => e.Price).HasColumnName("price");
            entity.Property(e => e.PriceJas).HasColumnName("price_jas");
            entity.Property(e => e.PriceNn).HasColumnName("price_nn");
            entity.Property(e => e.Qr)
                .HasMaxLength(255)
                .HasColumnName("qr");
            entity.Property(e => e.Rectification).HasColumnName("rectification");
            entity.Property(e => e.Size)
                .HasMaxLength(50)
                .HasColumnName("size");
            entity.Property(e => e.SourceType).HasColumnName("source_type");
            entity.Property(e => e.Surface)
                .HasMaxLength(3)
                .HasColumnName("surface");
            entity.Property(e => e.TypeOrder).HasColumnName("type_order");
            entity.Property(e => e.Unit)
                .HasMaxLength(50)
                .HasColumnName("unit");
        });

        modelBuilder.Entity<PdfPriceTagHistory>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("pdf_price_tag_history");

            entity.Property(e => e.ChangeDate)
                .HasDefaultValueSql("(getdate())")
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.ColumnName)
                .HasMaxLength(50)
                .HasColumnName("column_name");
            entity.Property(e => e.NewValue)
                .HasMaxLength(255)
                .HasColumnName("new_value");
            entity.Property(e => e.OldValue)
                .HasMaxLength(255)
                .HasColumnName("old_value");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");

            entity.HasOne(d => d.RegNumberNavigation).WithMany()
                .HasForeignKey(d => d.RegNumber)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_price_tag_history_pdf_price_tag");
        });

        modelBuilder.Entity<PdfPtEmail>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK_smtp_email");

            entity.ToTable("pdf_pt_email", tb => tb.HasTrigger("trg_AfterInsert_smtp_email"));

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Bcc)
                .HasMaxLength(1000)
                .HasColumnName("bcc");
            entity.Property(e => e.Body)
                .HasColumnType("ntext")
                .HasColumnName("body");
            entity.Property(e => e.CreatedDate)
                .HasColumnType("datetime")
                .HasColumnName("created_date");
            entity.Property(e => e.FromEmail)
                .HasMaxLength(50)
                .HasColumnName("from_email");
            entity.Property(e => e.IsBodyHtml).HasColumnName("is_body_html");
            entity.Property(e => e.SentDate)
                .HasColumnType("datetime")
                .HasColumnName("sent_date");
            entity.Property(e => e.Subject)
                .HasMaxLength(1000)
                .HasColumnName("subject");
            entity.Property(e => e.ToEmail)
                .HasMaxLength(1000)
                .HasColumnName("to_email");
        });

        modelBuilder.Entity<PdfPtEmailAttachment>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK_pdf_pt_email_stand");

            entity.ToTable("pdf_pt_email_attachment");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.IdEmail).HasColumnName("id_email");
            entity.Property(e => e.IdFile).HasColumnName("id_file");

            entity.HasOne(d => d.IdEmailNavigation).WithMany(p => p.PdfPtEmailAttachments)
                .HasForeignKey(d => d.IdEmail)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_pt_email_stand_pdf_pt_smtp_email");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfPtEmailAttachments)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_pt_email_attachment_pdf_pt_file");
        });

        modelBuilder.Entity<PdfPtFile>(entity =>
        {
            entity.ToTable("pdf_pt_file", tb => tb.HasTrigger("trg_AfterUpdate_pdf_pt_file"));

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.CreatedDate)
                .HasColumnType("datetime")
                .HasColumnName("created_date");
            entity.Property(e => e.FilePath)
                .HasMaxLength(1000)
                .HasColumnName("file_path");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdStand).HasColumnName("id_stand");
            entity.Property(e => e.IdType).HasColumnName("id_type");
            entity.Property(e => e.UpdatedDate)
                .HasColumnType("datetime")
                .HasColumnName("updated_date");

            entity.HasOne(d => d.IdCompanyNavigation).WithMany(p => p.PdfPtFiles)
                .HasForeignKey(d => d.IdCompany)
                .HasConstraintName("FK_pdf_pt_file_pdf_company");

            entity.HasOne(d => d.IdStandNavigation).WithMany(p => p.PdfPtFiles)
                .HasForeignKey(d => d.IdStand)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_pt_file_pdf_pt_stand");

            entity.HasOne(d => d.IdTypeNavigation).WithMany(p => p.PdfPtFiles)
                .HasForeignKey(d => d.IdType)
                .HasConstraintName("FK_pdf_pt_file_pdf_pt_type");
        });

        modelBuilder.Entity<PdfPtPlate>(entity =>
        {
            entity.ToTable("pdf_pt_plate", tb =>
                {
                    tb.HasTrigger("trg_AfterInsert_pdf_pt_plate");
                    tb.HasTrigger("trg_AfterUpdate_pdf_pt_plate");
                });

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Description)
                .HasMaxLength(255)
                .HasColumnName("description");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.IdMkPlate).HasColumnName("id_mk_plate");
            entity.Property(e => e.IdPtStand).HasColumnName("id_pt_stand");
            entity.Property(e => e.Picture)
                .HasMaxLength(255)
                .HasColumnName("picture");
            entity.Property(e => e.PlateOrder).HasColumnName("plate_order");
            entity.Property(e => e.Qr)
                .HasMaxLength(1000)
                .HasColumnName("qr");

            entity.HasOne(d => d.IdPtStandNavigation).WithMany(p => p.PdfPtPlates)
                .HasForeignKey(d => d.IdPtStand)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_pt_plate_pdf_pt_stand");
        });

        modelBuilder.Entity<PdfPtPlateItem>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK_dbf_pt_plate_item");

            entity.ToTable("pdf_pt_plate_item", tb =>
                {
                    tb.HasTrigger("trg_AfterInsert_pdf_pt_plate_item");
                    tb.HasTrigger("trg_AfterUpdate_pdf_pt_plate_item");
                });

            entity.HasIndex(e => e.Id, "UX_pdf_pt_plate_item_fulltext_key").IsUnique();

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.IdMkPlateItem).HasColumnName("id_mk_plate_item");
            entity.Property(e => e.IdPtPlate).HasColumnName("id_pt_plate");
            entity.Property(e => e.ItemOrder).HasColumnName("item_order");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");
            entity.Property(e => e.SeriesItem).HasColumnName("series_item");

            entity.HasOne(d => d.IdPtPlateNavigation).WithMany(p => p.PdfPtPlateItems)
                .HasForeignKey(d => d.IdPtPlate)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_pt_plate_item_pdf_pt_plate");

            entity.HasOne(d => d.RegNumberNavigation).WithMany(p => p.PdfPtPlateItems)
                .HasForeignKey(d => d.RegNumber)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_pt_plate_item_pdf_price_tag");
        });

        modelBuilder.Entity<PdfPtPlateItemHistory>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("pdf_pt_plate_item_history");

            entity.Property(e => e.ChangeDate)
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.ColumnName)
                .HasMaxLength(50)
                .HasColumnName("column_name");
            entity.Property(e => e.IdPtPlate).HasColumnName("id_pt_plate");
            entity.Property(e => e.NewValue)
                .HasMaxLength(255)
                .HasColumnName("new_value");
            entity.Property(e => e.OldValue)
                .HasMaxLength(255)
                .HasColumnName("old_value");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");
        });

        modelBuilder.Entity<PdfPtStand>(entity =>
        {
            entity.ToTable("pdf_pt_stand", tb =>
                {
                    tb.HasTrigger("trg_AfterInsert_pdf_pt_stand");
                    tb.HasTrigger("trg_AfterUpdate_pdf_pt_stand");
                });

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.B2b).HasColumnName("b2b");
            entity.Property(e => e.B2c).HasColumnName("b2c");
            entity.Property(e => e.ChangeEmail).HasColumnName("change_email");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.IdMkProducer).HasColumnName("id_mk_producer");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.Picture)
                .HasMaxLength(255)
                .HasColumnName("picture");
            entity.Property(e => e.PiecePriceTag).HasColumnName("piece_price_tag");
            entity.Property(e => e.PlatePriceTag).HasColumnName("plate_price_tag");
            entity.Property(e => e.Qr)
                .HasMaxLength(1000)
                .HasColumnName("qr");
            entity.Property(e => e.SecondPicture)
                .HasMaxLength(255)
                .HasColumnName("second_picture");
            entity.Property(e => e.Type).HasColumnName("type");
            entity.Property(e => e.UnitCount).HasColumnName("unit_count");
        });

        modelBuilder.Entity<PdfPtStandHistory>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("pdf_pt_stand_history");

            entity.Property(e => e.ChangeDate)
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.CheckDate)
                .HasColumnType("datetime")
                .HasColumnName("check_date");
            entity.Property(e => e.ColumnName)
                .HasMaxLength(50)
                .HasColumnName("column_name");
            entity.Property(e => e.IdPtPlate).HasColumnName("id_pt_plate");
            entity.Property(e => e.IdPtPlateItem).HasColumnName("id_pt_plate_item");
            entity.Property(e => e.IdPtStand).HasColumnName("id_pt_stand");
            entity.Property(e => e.NewValue)
                .HasMaxLength(255)
                .HasColumnName("new_value");
            entity.Property(e => e.OldValue)
                .HasMaxLength(255)
                .HasColumnName("old_value");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");
        });

        modelBuilder.Entity<PdfPtType>(entity =>
        {
            entity.ToTable("pdf_pt_type");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Name)
                .HasMaxLength(50)
                .HasColumnName("name");
        });

        modelBuilder.Entity<PdfQr>(entity =>
        {
            entity.ToTable("pdf_qr");

            entity.HasIndex(e => new { e.IdCompany, e.IdFile, e.PageIndex, e.ObjectKey }, "UQ_company_file_index_key").IsUnique();

            entity.HasIndex(e => new { e.IdFile, e.IdCompany, e.PageIndex }, "ix_pdf_qr_file_company_page");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.ObjectKey)
                .HasMaxLength(10)
                .HasColumnName("object_key");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.Redirect)
                .HasMaxLength(1000)
                .HasColumnName("redirect");
            entity.Property(e => e.ReplaceValue)
                .HasMaxLength(1000)
                .HasColumnName("replace_value");
            entity.Property(e => e.ValidDate)
                .HasColumnType("datetime")
                .HasColumnName("valid_date");
            entity.Property(e => e.Value)
                .HasMaxLength(1000)
                .HasColumnName("value");

            entity.HasOne(d => d.IdCompanyNavigation).WithMany(p => p.PdfQrs)
                .HasForeignKey(d => d.IdCompany)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_qr_pdf_company");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfQrs)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_qr_pdf_file");
        });

        modelBuilder.Entity<PdfRedirect>(entity =>
        {
            entity
                .HasNoKey()
                .ToTable("pdf_redirect");

            entity.Property(e => e.RedirectFrom)
                .HasMaxLength(1000)
                .HasColumnName("redirect_from");
            entity.Property(e => e.RedirectTo)
                .HasMaxLength(1000)
                .HasColumnName("redirect_to");
        });

        modelBuilder.Entity<PdfSlider>(entity =>
        {
            entity.ToTable("pdf_slider");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.Img)
                .HasMaxLength(50)
                .HasColumnName("img");
            entity.Property(e => e.SliderOrder).HasColumnName("slider_order");
            entity.Property(e => e.Type).HasColumnName("type");
            entity.Property(e => e.Url)
                .HasMaxLength(255)
                .HasColumnName("url");

            entity.HasOne(d => d.IdCatalogNavigation).WithMany(p => p.PdfSliders)
                .HasForeignKey(d => d.IdCatalog)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_slider_pdf_catalog");

            entity.HasOne(d => d.IdCompanyNavigation).WithMany(p => p.PdfSliders)
                .HasForeignKey(d => d.IdCompany)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_slider_pdf_company");
        });

        modelBuilder.Entity<PdfText>(entity =>
        {
            entity.ToTable("pdf_text");

            entity.HasIndex(e => new { e.IdFile, e.PageIndex }, "ix_pdf_text_file_page_value");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.FontName)
                .HasMaxLength(50)
                .HasColumnName("font_name");
            entity.Property(e => e.FontSize).HasColumnName("font_size");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.Value).HasColumnName("value");
            entity.Property(e => e.X).HasColumnName("x");
            entity.Property(e => e.Y).HasColumnName("y");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfTexts)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_text_pdf_file");
        });

        modelBuilder.Entity<Produkty>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK__Produkty__3214EC27DBCB10B8");

            entity.ToTable("Produkty");

            entity.Property(e => e.Id).HasColumnName("ID");
            entity.Property(e => e.PageCount)
                .HasComputedColumnSql("(([page_to]-[page_from])+(1))", false)
                .HasColumnName("page_count");
            entity.Property(e => e.PageFrom).HasColumnName("page_from");
            entity.Property(e => e.PageTo).HasColumnName("page_to");
            entity.Property(e => e.ParentId).HasColumnName("parent_id");
            entity.Property(e => e.Title)
                .HasMaxLength(255)
                .HasColumnName("title");
        });

        modelBuilder.Entity<Rabat>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("rabat");

            entity.Property(e => e.ItemName)
                .HasMaxLength(255)
                .HasColumnName("item_name");
            entity.Property(e => e.MopGroupPsku).HasColumnName("mop_group_psku");
            entity.Property(e => e.MopId).HasColumnName("mop_id");
            entity.Property(e => e.MopProducerSku).HasColumnName("mop_producer_sku");
            entity.Property(e => e.Rabat1).HasColumnName("rabat");
            entity.Property(e => e.SeriesName)
                .HasMaxLength(255)
                .HasColumnName("series_name");
        });

        modelBuilder.Entity<ViPdfCatalogDownload>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_catalog_download");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CatalogTitle)
                .HasMaxLength(255)
                .HasColumnName("catalog_title");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.CompanyName)
                .HasMaxLength(255)
                .HasColumnName("company_name");
            entity.Property(e => e.FilePath)
                .HasMaxLength(255)
                .HasColumnName("file_path");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.PageIndexInCatalog).HasColumnName("page_index_in_catalog");
        });

        modelBuilder.Entity<ViPdfCompanyCatalogFile>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_company_catalog_file");

            entity.Property(e => e.BeginIndexFrom).HasColumnName("begin_index_from");
            entity.Property(e => e.BeginIndexTo).HasColumnName("begin_index_to");
            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CatalogType)
                .HasMaxLength(50)
                .HasColumnName("catalog_type");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.ContentGroup)
                .HasMaxLength(50)
                .HasColumnName("content_group");
            entity.Property(e => e.EndingIndexFrom).HasColumnName("ending_index_from");
            entity.Property(e => e.EndingIndexTo).HasColumnName("ending_index_to");
            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndexFrom).HasColumnName("page_index_from");
            entity.Property(e => e.PageIndexTo).HasColumnName("page_index_to");
            entity.Property(e => e.PageOrder).HasColumnName("page_order");
            entity.Property(e => e.PageStartOn).HasColumnName("page_start_on");
            entity.Property(e => e.Path)
                .HasMaxLength(1000)
                .HasColumnName("path");
            entity.Property(e => e.PdfType)
                .HasMaxLength(50)
                .HasColumnName("pdf_type");
            entity.Property(e => e.Series)
                .HasMaxLength(255)
                .HasColumnName("series");
        });

        modelBuilder.Entity<ViPdfContent>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_content");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.Json).HasColumnName("json");
        });

        modelBuilder.Entity<ViPdfDownload>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_download");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CatalogName)
                .HasMaxLength(255)
                .HasColumnName("catalog_name");
            entity.Property(e => e.CatalogTitle)
                .HasMaxLength(255)
                .HasColumnName("catalog_title");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.CompanyName)
                .HasMaxLength(255)
                .HasColumnName("company_name");
            entity.Property(e => e.FilePath)
                .HasMaxLength(255)
                .HasColumnName("file_path");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.PageIndexInBody).HasColumnName("page_index_in_body");
            entity.Property(e => e.PageIndexInCatalog).HasColumnName("page_index_in_catalog");
        });

        modelBuilder.Entity<ViPdfFile>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_file");

            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndexFrom).HasColumnName("page_index_from");
            entity.Property(e => e.PageIndexTo).HasColumnName("page_index_to");
            entity.Property(e => e.Path)
                .HasMaxLength(255)
                .HasColumnName("path");
            entity.Property(e => e.ProductEndsAt).HasColumnName("product_ends_at");
            entity.Property(e => e.ProductStartsAt).HasColumnName("product_starts_at");
        });

        modelBuilder.Entity<ViPdfItem>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_item");

            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.PageIndexInFile).HasColumnName("page_index_in_file");
            entity.Property(e => e.RegNumber).HasColumnName("reg_number");
            entity.Property(e => e.ReplaceCode).HasColumnName("replace_code");
        });

        modelBuilder.Entity<ViPdfPagesCount>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_pages_count");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.Landscape).HasColumnName("landscape");
            entity.Property(e => e.PageStartOn).HasColumnName("page_start_on");
            entity.Property(e => e.PagesCount).HasColumnName("pages_count");
        });

        modelBuilder.Entity<ViPdfPriceTag>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_price_tag");

            entity.Property(e => e.Abrasion)
                .HasMaxLength(5)
                .HasColumnName("abrasion");
            entity.Property(e => e.Antislip)
                .HasMaxLength(5)
                .HasColumnName("antislip");
            entity.Property(e => e.Frost).HasColumnName("frost");
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
            entity.Property(e => e.SourceType).HasColumnName("source_type");
            entity.Property(e => e.StandId).HasColumnName("stand_id");
            entity.Property(e => e.StandName)
                .HasMaxLength(255)
                .HasColumnName("stand_name");
            entity.Property(e => e.Surface)
                .HasMaxLength(3)
                .HasColumnName("surface");
            entity.Property(e => e.Unit)
                .HasMaxLength(50)
                .HasColumnName("unit");
        });

        modelBuilder.Entity<ViPdfPriceTag1>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_price_tag_");

            entity.Property(e => e.Abrasion)
                .HasMaxLength(5)
                .HasColumnName("abrasion");
            entity.Property(e => e.Antislip)
                .HasMaxLength(5)
                .HasColumnName("antislip");
            entity.Property(e => e.Frost).HasColumnName("frost");
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
            entity.Property(e => e.SourceType).HasColumnName("source_type");
            entity.Property(e => e.StandId).HasColumnName("stand_id");
            entity.Property(e => e.StandName)
                .HasMaxLength(255)
                .HasColumnName("stand_name");
            entity.Property(e => e.Surface)
                .HasMaxLength(3)
                .HasColumnName("surface");
            entity.Property(e => e.Unit)
                .HasMaxLength(50)
                .HasColumnName("unit");
        });

        modelBuilder.Entity<ViPdfPtChangeEmail>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_pt_change_email");

            entity.Property(e => e.B2b).HasColumnName("b2b");
            entity.Property(e => e.B2bPiDate)
                .HasColumnType("datetime")
                .HasColumnName("b2b_pi_date");
            entity.Property(e => e.B2bPiFileId).HasColumnName("b2b_pi_file_id");
            entity.Property(e => e.B2bPlDate)
                .HasColumnType("datetime")
                .HasColumnName("b2b_pl_date");
            entity.Property(e => e.B2bPlFileId).HasColumnName("b2b_pl_file_id");
            entity.Property(e => e.B2c).HasColumnName("b2c");
            entity.Property(e => e.B2cPiDate)
                .HasColumnType("datetime")
                .HasColumnName("b2c_pi_date");
            entity.Property(e => e.B2cPiFileId).HasColumnName("b2c_pi_file_id");
            entity.Property(e => e.B2cPlDate)
                .HasColumnType("datetime")
                .HasColumnName("b2c_pl_date");
            entity.Property(e => e.B2cPlFileId).HasColumnName("b2c_pl_file_id");
            entity.Property(e => e.ChangeDate)
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdStand).HasColumnName("id_stand");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.PiecePriceTag).HasColumnName("piece_price_tag");
            entity.Property(e => e.PlatePriceTag).HasColumnName("plate_price_tag");
            entity.Property(e => e.SentDate)
                .HasColumnType("datetime")
                .HasColumnName("sent_date");
            entity.Property(e => e.Type).HasColumnName("type");
        });

        modelBuilder.Entity<ViPdfPtChangedStand>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_pt_changed_stand");

            entity.Property(e => e.B2b).HasColumnName("b2b");
            entity.Property(e => e.B2c).HasColumnName("b2c");
            entity.Property(e => e.ChangeDate)
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.FilePath)
                .HasMaxLength(1000)
                .HasColumnName("file_path");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdEmail).HasColumnName("id_email");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdStand).HasColumnName("id_stand");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.PiecePriceTag).HasColumnName("piece_price_tag");
            entity.Property(e => e.PlatePriceTag).HasColumnName("plate_price_tag");
            entity.Property(e => e.SentDate)
                .HasColumnType("datetime")
                .HasColumnName("sent_date");
            entity.Property(e => e.Type).HasColumnName("type");
        });

        modelBuilder.Entity<ViPdfPtPlate>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_pt_plate");

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
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdPtPlate).HasColumnName("id_pt_plate");
            entity.Property(e => e.IdPtStand).HasColumnName("id_pt_stand");
            entity.Property(e => e.Inserted).HasColumnName("inserted");
            entity.Property(e => e.ItemOrder).HasColumnName("item_order");
            entity.Property(e => e.Name)
                .HasMaxLength(4000)
                .HasColumnName("name");
            entity.Property(e => e.OrigName)
                .HasMaxLength(255)
                .HasColumnName("orig_name");
            entity.Property(e => e.Outlet).HasColumnName("outlet");
            entity.Property(e => e.OutletQr)
                .HasMaxLength(255)
                .HasColumnName("outlet_qr");
            entity.Property(e => e.Picture)
                .HasMaxLength(255)
                .HasColumnName("picture");
            entity.Property(e => e.PlateOrder).HasColumnName("plate_order");
            entity.Property(e => e.PlateQr)
                .HasMaxLength(1000)
                .HasColumnName("plate_qr");
            entity.Property(e => e.Price).HasColumnName("price");
            entity.Property(e => e.PriceJas).HasColumnName("price_jas");
            entity.Property(e => e.PriceNn).HasColumnName("price_nn");
            entity.Property(e => e.ProductType)
                .HasMaxLength(8)
                .IsUnicode(false)
                .HasColumnName("product_type");
            entity.Property(e => e.Qr)
                .HasMaxLength(255)
                .HasColumnName("qr");
            entity.Property(e => e.Rectification).HasColumnName("rectification");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(10)
                .HasColumnName("reg_number");
            entity.Property(e => e.SeriesItem).HasColumnName("series_item");
            entity.Property(e => e.Size)
                .HasMaxLength(4000)
                .HasColumnName("size");
            entity.Property(e => e.SourceType).HasColumnName("source_type");
            entity.Property(e => e.Surface)
                .HasMaxLength(3)
                .HasColumnName("surface");
            entity.Property(e => e.TypeOrder).HasColumnName("type_order");
            entity.Property(e => e.Unit)
                .HasMaxLength(9)
                .HasColumnName("unit");
        });

        modelBuilder.Entity<ViPdfPtPlateBak>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_pt_plate_bak");

            entity.Property(e => e.Abrasion)
                .HasMaxLength(5)
                .HasColumnName("abrasion");
            entity.Property(e => e.Antislip)
                .HasMaxLength(5)
                .HasColumnName("antislip");
            entity.Property(e => e.Discarded).HasColumnName("discarded");
            entity.Property(e => e.Discount).HasColumnName("discount");
            entity.Property(e => e.Frost).HasColumnName("frost");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdPtPlate).HasColumnName("id_pt_plate");
            entity.Property(e => e.IdPtStand).HasColumnName("id_pt_stand");
            entity.Property(e => e.Inserted).HasColumnName("inserted");
            entity.Property(e => e.ItemOrder).HasColumnName("item_order");
            entity.Property(e => e.Name)
                .HasMaxLength(4000)
                .HasColumnName("name");
            entity.Property(e => e.OrigName)
                .HasMaxLength(255)
                .HasColumnName("orig_name");
            entity.Property(e => e.Outlet).HasColumnName("outlet");
            entity.Property(e => e.OutletQr)
                .HasMaxLength(255)
                .HasColumnName("outlet_qr");
            entity.Property(e => e.Picture)
                .HasMaxLength(255)
                .HasColumnName("picture");
            entity.Property(e => e.PlateOrder).HasColumnName("plate_order");
            entity.Property(e => e.PlateQr)
                .HasMaxLength(1000)
                .HasColumnName("plate_qr");
            entity.Property(e => e.Price).HasColumnName("price");
            entity.Property(e => e.PriceJas).HasColumnName("price_jas");
            entity.Property(e => e.PriceNn).HasColumnName("price_nn");
            entity.Property(e => e.ProductType)
                .HasMaxLength(8)
                .IsUnicode(false)
                .HasColumnName("product_type");
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
            entity.Property(e => e.SourceType).HasColumnName("source_type");
            entity.Property(e => e.Surface)
                .HasMaxLength(3)
                .HasColumnName("surface");
            entity.Property(e => e.TypeOrder).HasColumnName("type_order");
            entity.Property(e => e.Unit)
                .HasMaxLength(9)
                .HasColumnName("unit");
        });

        modelBuilder.Entity<ViPdfPtPriceTagHistory>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_pt_price_tag_history");

            entity.Property(e => e.B2b).HasColumnName("b2b");
            entity.Property(e => e.B2c).HasColumnName("b2c");
            entity.Property(e => e.ChangeDate)
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdStand).HasColumnName("id_stand");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.PiecePriceTag).HasColumnName("piece_price_tag");
            entity.Property(e => e.PlatePriceTag).HasColumnName("plate_price_tag");
            entity.Property(e => e.SentDate)
                .HasColumnType("datetime")
                .HasColumnName("sent_date");
            entity.Property(e => e.Type).HasColumnName("type");
        });

        modelBuilder.Entity<ViPdfPtStandHistory>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_pt_stand_history");

            entity.Property(e => e.B2b).HasColumnName("b2b");
            entity.Property(e => e.B2c).HasColumnName("b2c");
            entity.Property(e => e.ChangeDate)
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdStand).HasColumnName("id_stand");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.PiecePriceTag).HasColumnName("piece_price_tag");
            entity.Property(e => e.PlatePriceTag).HasColumnName("plate_price_tag");
            entity.Property(e => e.SentDate)
                .HasColumnType("datetime")
                .HasColumnName("sent_date");
            entity.Property(e => e.Type).HasColumnName("type");
        });

        modelBuilder.Entity<ViPdfQr>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_qr");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.ObjectKey)
                .HasMaxLength(10)
                .HasColumnName("object_key");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.PageIndexInFile).HasColumnName("page_index_in_file");
            entity.Property(e => e.ReplaceValue)
                .HasMaxLength(1000)
                .HasColumnName("replace_value");
        });

        modelBuilder.Entity<ViPdfQrRedirect>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_qr_redirect");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.ConcatPtValue).HasColumnName("concat_pt_value");
            entity.Property(e => e.GlobalPageIndex).HasColumnName("global_page_index");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.Path)
                .HasMaxLength(1000)
                .HasColumnName("path");
            entity.Property(e => e.PqValue)
                .HasMaxLength(1000)
                .HasColumnName("pq_value");
            entity.Property(e => e.TextFile).HasColumnName("text_file");
            entity.Property(e => e.TextPageIndexGlobal).HasColumnName("text_page_index_global");
            entity.Property(e => e.TextPageIndexLocal).HasColumnName("text_page_index_local");
        });

        modelBuilder.Entity<ViPdfRedirect>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_redirect");

            entity.Property(e => e.RedirectFrom)
                .HasMaxLength(1000)
                .HasColumnName("redirect_from");
            entity.Property(e => e.RedirectTo)
                .HasMaxLength(1000)
                .HasColumnName("redirect_to");
        });

        modelBuilder.Entity<ViPdfSearchText>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_search_text");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.PageIndexInFile).HasColumnName("page_index_in_file");
            entity.Property(e => e.Value).HasColumnName("value");
            entity.Property(e => e.X).HasColumnName("x");
            entity.Property(e => e.Y).HasColumnName("y");
        });

        modelBuilder.Entity<ViPdfSlider>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_slider");

            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.ImgSrc)
                .HasMaxLength(68)
                .HasColumnName("img_src");
            entity.Property(e => e.SliderOrder).HasColumnName("slider_order");
            entity.Property(e => e.Type).HasColumnName("type");
            entity.Property(e => e.Url)
                .HasMaxLength(516)
                .HasColumnName("url");
        });

        modelBuilder.Entity<ViPdfText>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_pdf_text");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.PageIndexInFile).HasColumnName("page_index_in_file");
            entity.Property(e => e.Value).HasColumnName("value");
            entity.Property(e => e.X).HasColumnName("x");
            entity.Property(e => e.Y).HasColumnName("y");
        });

        modelBuilder.Entity<View1>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("View_1");

            entity.Property(e => e.CatalogKey)
                .HasMaxLength(255)
                .HasColumnName("catalog_key");
            entity.Property(e => e.CompanyKey)
                .HasMaxLength(255)
                .HasColumnName("company_key");
            entity.Property(e => e.ConcatPtValue).HasColumnName("concat_pt_value");
            entity.Property(e => e.GlobalPageIndex).HasColumnName("global_page_index");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.Path)
                .HasMaxLength(1000)
                .HasColumnName("path");
            entity.Property(e => e.PqValue)
                .HasMaxLength(1000)
                .HasColumnName("pq_value");
            entity.Property(e => e.TextFile).HasColumnName("text_file");
            entity.Property(e => e.TextPageIndexGlobal).HasColumnName("text_page_index_global");
            entity.Property(e => e.TextPageIndexLocal).HasColumnName("text_page_index_local");
        });

        OnModelCreatingPartial(modelBuilder);
    }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder);
}
