using System;
using System.Collections.Generic;
using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasMtzDb;

public partial class JasMtzDbContext : DbContext
{
    public JasMtzDbContext(DbContextOptions<JasMtzDbContext> options)
        : base(options)
    {
    }

    public virtual DbSet<JasDepartment> JasDepartments { get; set; }

    public virtual DbSet<JasProducer> JasProducers { get; set; }

    public virtual DbSet<JasStore> JasStores { get; set; }

    public virtual DbSet<MtzAttribute> MtzAttributes { get; set; }

    public virtual DbSet<MtzCategory> MtzCategories { get; set; }

    public virtual DbSet<MtzOrder> MtzOrders { get; set; }

    public virtual DbSet<MtzOrderItem> MtzOrderItems { get; set; }

    public virtual DbSet<MtzProduct> MtzProducts { get; set; }

    public virtual DbSet<MtzProductAttribute> MtzProductAttributes { get; set; }

    public virtual DbSet<PdfCatalog> PdfCatalogs { get; set; }

    public virtual DbSet<PdfCompany> PdfCompanies { get; set; }

    public virtual DbSet<PdfCompanyCatalog> PdfCompanyCatalogs { get; set; }

    public virtual DbSet<PdfFile> PdfFiles { get; set; }

    public virtual DbSet<PdfQr> PdfQrs { get; set; }

    public virtual DbSet<PdfRegNumber> PdfRegNumbers { get; set; }

    public virtual DbSet<PtgStandCompany> PtgStandCompanies { get; set; }

    public virtual DbSet<PtgStandSearch> PtgStandSearches { get; set; }

    public virtual DbSet<ViMkMtzUser> ViMkMtzUsers { get; set; }

    public virtual DbSet<ViMtzUser> ViMtzUsers { get; set; }

    public virtual DbSet<ViPtgPdfPtPlate> ViPtgPdfPtPlates { get; set; }

    public virtual DbSet<ViPtgStand> ViPtgStands { get; set; }

    public virtual DbSet<ViPtgStandCompany> ViPtgStandCompanies { get; set; }

    public virtual DbSet<ViPtgStandCompany1> ViPtgStandCompanies1 { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
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

            entity.HasOne(d => d.IdStoreNavigation).WithMany(p => p.JasDepartments)
                .HasForeignKey(d => d.IdStore)
                .HasConstraintName("FK_jas_department_jas_store");
        });

        modelBuilder.Entity<JasProducer>(entity =>
        {
            entity.ToTable("jas_producer");

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

        modelBuilder.Entity<MtzAttribute>(entity =>
        {
            entity.HasKey(e => e.Id).HasName("PK_mtz_product_property");

            entity.ToTable("mtz_attribute");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.Name)
                .HasMaxLength(50)
                .HasColumnName("name");
        });

        modelBuilder.Entity<MtzCategory>(entity =>
        {
            entity.ToTable("mtz_category");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.IdParent).HasColumnName("id_parent");
            entity.Property(e => e.Image)
                .HasMaxLength(255)
                .HasColumnName("image");
            entity.Property(e => e.Level).HasColumnName("level");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.SeoUrl)
                .HasMaxLength(255)
                .HasColumnName("seo_url");

            entity.HasOne(d => d.IdParentNavigation).WithMany(p => p.InverseIdParentNavigation)
                .HasForeignKey(d => d.IdParent)
                .HasConstraintName("FK_mtz_category_mtz_category");
        });

        modelBuilder.Entity<MtzOrder>(entity =>
        {
            entity.ToTable("mtz_order");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Comment).HasColumnName("comment");
            entity.Property(e => e.IdDepartment).HasColumnName("id_department");
            entity.Property(e => e.IdUser)
                .HasMaxLength(450)
                .HasColumnName("id_user");
            entity.Property(e => e.OrderDate)
                .HasColumnType("datetime")
                .HasColumnName("order_date");
            entity.Property(e => e.SentDate)
                .HasColumnType("datetime")
                .HasColumnName("sent_date");
            entity.Property(e => e.State).HasColumnName("state");
            entity.Property(e => e.StoreId).HasColumnName("store_id");
            entity.Property(e => e.UserEmail)
                .HasMaxLength(50)
                .HasColumnName("user_email");
            entity.Property(e => e.UserName)
                .HasMaxLength(50)
                .HasColumnName("user_name");

            entity.HasOne(d => d.IdDepartmentNavigation).WithMany(p => p.MtzOrders)
                .HasForeignKey(d => d.IdDepartment)
                .HasConstraintName("FK_mtz_order_jas_department");

            entity.HasOne(d => d.Store).WithMany(p => p.MtzOrders)
                .HasForeignKey(d => d.StoreId)
                .HasConstraintName("FK_mtz_order_jas_store");
        });

        modelBuilder.Entity<MtzOrderItem>(entity =>
        {
            entity.ToTable("mtz_order_item");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Amount).HasColumnName("amount");
            entity.Property(e => e.Comment)
                .HasMaxLength(255)
                .HasColumnName("comment");
            entity.Property(e => e.Description)
                .HasMaxLength(255)
                .HasColumnName("description");
            entity.Property(e => e.IdOrder).HasColumnName("id_order");
            entity.Property(e => e.IdProduct).HasColumnName("id_product");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.NamesOfEmployees)
                .HasMaxLength(255)
                .HasColumnName("names_of_employees");
            entity.Property(e => e.OrderUnit)
                .HasMaxLength(50)
                .HasColumnName("order_unit");
            entity.Property(e => e.PackageSize).HasColumnName("package_size");
            entity.Property(e => e.SelectedSize)
                .HasMaxLength(255)
                .HasColumnName("selected_size");
            entity.Property(e => e.State).HasColumnName("state");

            entity.HasOne(d => d.IdOrderNavigation).WithMany(p => p.MtzOrderItems)
                .HasForeignKey(d => d.IdOrder)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_mtz_order_item_mtz_order");

            entity.HasOne(d => d.IdProductNavigation).WithMany(p => p.MtzOrderItems)
                .HasForeignKey(d => d.IdProduct)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_mtz_order_item_mtz_product");
        });

        modelBuilder.Entity<MtzProduct>(entity =>
        {
            entity.ToTable("mtz_product");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.Description).HasColumnName("description");
            entity.Property(e => e.Filter).HasColumnName("filter");
            entity.Property(e => e.IdCategory).HasColumnName("id_category");
            entity.Property(e => e.Image)
                .HasMaxLength(255)
                .HasColumnName("image");
            entity.Property(e => e.MinAmount).HasColumnName("min_amount");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.OrderUnit)
                .HasMaxLength(50)
                .HasColumnName("order_unit");
            entity.Property(e => e.PackageSize).HasColumnName("package_size");
            entity.Property(e => e.SeoUrl)
                .HasMaxLength(255)
                .HasColumnName("seo_url");
            entity.Property(e => e.Specification).HasColumnName("specification");

            entity.HasOne(d => d.IdCategoryNavigation).WithMany(p => p.MtzProducts)
                .HasForeignKey(d => d.IdCategory)
                .HasConstraintName("FK_mtz_product_mtz_category");
        });

        modelBuilder.Entity<MtzProductAttribute>(entity =>
        {
            entity.HasKey(e => new { e.IdProduct, e.IdAttribute, e.Value });

            entity.ToTable("mtz_product_attribute");

            entity.Property(e => e.IdProduct).HasColumnName("id_product");
            entity.Property(e => e.IdAttribute).HasColumnName("id_attribute");
            entity.Property(e => e.Value)
                .HasMaxLength(50)
                .HasColumnName("value");
            entity.Property(e => e.ProductCode)
                .HasMaxLength(50)
                .HasColumnName("product_code");

            entity.HasOne(d => d.IdAttributeNavigation).WithMany(p => p.MtzProductAttributes)
                .HasForeignKey(d => d.IdAttribute)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_mtz_product_attribute_mtz_attribute");

            entity.HasOne(d => d.IdProductNavigation).WithMany(p => p.MtzProductAttributes)
                .HasForeignKey(d => d.IdProduct)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_mtz_product_attribute_mtz_product");
        });

        modelBuilder.Entity<PdfCatalog>(entity =>
        {
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
            entity.HasKey(e => e.Id).HasName("PK_pdf_company_1");

            entity.ToTable("pdf_company");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.CompanyCode)
                .HasMaxLength(10)
                .HasColumnName("company_code");
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

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.IdCatalog).HasColumnName("id_catalog");
            entity.Property(e => e.IdCompany).HasColumnName("id_company");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndexFrom).HasColumnName("page_index_from");
            entity.Property(e => e.PageIndexTo).HasColumnName("page_index_to");
            entity.Property(e => e.PageStartOn).HasColumnName("page_start_on");
            entity.Property(e => e.PartName)
                .HasMaxLength(50)
                .HasColumnName("part_name");

            entity.HasOne(d => d.IdCatalogNavigation).WithMany(p => p.PdfCompanyCatalogs)
                .HasForeignKey(d => d.IdCatalog)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_company_catalog_pdf_catalog");

            entity.HasOne(d => d.IdCompanyNavigation).WithMany(p => p.PdfCompanyCatalogs)
                .HasForeignKey(d => d.IdCompany)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_company_catalog_pdf_company");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfCompanyCatalogs)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_company_catalog_pdf_file");
        });

        modelBuilder.Entity<PdfFile>(entity =>
        {
            entity.ToTable("pdf_file");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.CompanyCode)
                .HasMaxLength(50)
                .HasColumnName("company_code");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.Modified)
                .HasColumnType("datetime")
                .HasColumnName("modified");
            entity.Property(e => e.NumberOfPages).HasColumnName("number_of_pages");
            entity.Property(e => e.PartName)
                .HasMaxLength(50)
                .HasColumnName("part_name");
            entity.Property(e => e.Path)
                .HasMaxLength(1000)
                .HasColumnName("path");
            entity.Property(e => e.Size).HasColumnName("size");
        });

        modelBuilder.Entity<PdfQr>(entity =>
        {
            entity.ToTable("pdf_qr");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Height).HasColumnName("height");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.PosX).HasColumnName("pos_x");
            entity.Property(e => e.PosY).HasColumnName("pos_y");
            entity.Property(e => e.Value)
                .HasMaxLength(1000)
                .HasColumnName("value");
            entity.Property(e => e.Width).HasColumnName("width");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfQrs)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_qr_pdf_file");
        });

        modelBuilder.Entity<PdfRegNumber>(entity =>
        {
            entity.ToTable("pdf_reg_number", tb => tb.HasTrigger("trg_pdf_reg_number_change_date"));

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.ChangeDate)
                .HasColumnType("datetime")
                .HasColumnName("change_date");
            entity.Property(e => e.EshopSeriesCode)
                .HasMaxLength(50)
                .HasColumnName("eshop_series_code");
            entity.Property(e => e.EshopSeriesName)
                .HasMaxLength(50)
                .HasColumnName("eshop_series_name");
            entity.Property(e => e.IdFile).HasColumnName("id_file");
            entity.Property(e => e.JasPrice)
                .HasMaxLength(20)
                .HasColumnName("jas_price");
            entity.Property(e => e.NnPrice)
                .HasMaxLength(20)
                .HasColumnName("nn_price");
            entity.Property(e => e.PageIndex).HasColumnName("page_index");
            entity.Property(e => e.Price).HasColumnName("price");
            entity.Property(e => e.Rabat).HasColumnName("rabat");
            entity.Property(e => e.RegNumber)
                .HasMaxLength(7)
                .HasColumnName("reg_number");
            entity.Property(e => e.Unit)
                .HasMaxLength(10)
                .HasColumnName("unit");

            entity.HasOne(d => d.IdFileNavigation).WithMany(p => p.PdfRegNumbers)
                .HasForeignKey(d => d.IdFile)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_pdf_reg_number_pdf_file");
        });

        modelBuilder.Entity<PtgStandCompany>(entity =>
        {
            entity.ToTable("ptg_stand_company");

            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.Cipa).HasColumnName("cipa");
            entity.Property(e => e.Ico)
                .HasMaxLength(8)
                .HasColumnName("ico");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdStand).HasColumnName("id_stand");
        });

        modelBuilder.Entity<PtgStandSearch>(entity =>
        {
            entity.ToTable("ptg_stand_search");

            entity.HasIndex(e => e.Code, "IX_ptg_stand_search_Code");

            entity.HasIndex(e => e.RegNumber, "IX_ptg_stand_search_RegNum");

            entity.HasIndex(e => e.Size, "IX_ptg_stand_search_Size");

            entity.Property(e => e.Code).HasMaxLength(50);
            entity.Property(e => e.ItemName).HasMaxLength(200);
            entity.Property(e => e.K2producerName)
                .HasMaxLength(200)
                .HasColumnName("K2ProducerName");
            entity.Property(e => e.K2seriesName)
                .HasMaxLength(200)
                .HasColumnName("K2SeriesName");
            entity.Property(e => e.Name).HasMaxLength(200);
            entity.Property(e => e.RegNumber).HasMaxLength(50);
            entity.Property(e => e.Size).HasMaxLength(100);
            entity.Property(e => e.StandProducerAlias).HasMaxLength(200);
            entity.Property(e => e.StandProducerName).HasMaxLength(200);
        });

        modelBuilder.Entity<ViMkMtzUser>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_mk_mtz_users");

            entity.Property(e => e.Email)
                .HasMaxLength(255)
                .HasColumnName("email");
            entity.Property(e => e.InternalLogin)
                .HasMaxLength(255)
                .HasColumnName("internal_login");
            entity.Property(e => e.Name)
                .HasMaxLength(255)
                .HasColumnName("name");
            entity.Property(e => e.Store)
                .HasColumnType("numeric(10, 0)")
                .HasColumnName("store");
            entity.Property(e => e.Username)
                .HasMaxLength(255)
                .HasColumnName("username");
        });

        modelBuilder.Entity<ViMtzUser>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_mtz_users");

            entity.Property(e => e.Email).HasMaxLength(256);
            entity.Property(e => e.NormalizedEmail).HasMaxLength(256);
            entity.Property(e => e.UserName).HasMaxLength(256);
        });

        modelBuilder.Entity<ViPtgPdfPtPlate>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_pdf_pt_plate");

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

        modelBuilder.Entity<ViPtgStand>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_stand");

            entity.Property(e => e.Code).HasMaxLength(50);
            entity.Property(e => e.ItemName).HasMaxLength(255);
            entity.Property(e => e.K2producerName)
                .HasMaxLength(50)
                .HasColumnName("K2ProducerName");
            entity.Property(e => e.K2seriesName)
                .HasMaxLength(100)
                .HasColumnName("K2SeriesName");
            entity.Property(e => e.Name).HasMaxLength(255);
            entity.Property(e => e.Picture).HasMaxLength(255);
            entity.Property(e => e.Qr).HasMaxLength(1000);
            entity.Property(e => e.RegNumber).HasMaxLength(30);
            entity.Property(e => e.SecondPicture).HasMaxLength(255);
            entity.Property(e => e.Size).HasMaxLength(4000);
            entity.Property(e => e.StandProducerAlias).HasMaxLength(50);
            entity.Property(e => e.StandProducerName).HasMaxLength(50);
        });

        modelBuilder.Entity<ViPtgStandCompany>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_stand_company");

            entity.Property(e => e.B2b).HasColumnName("b2b");
            entity.Property(e => e.B2c).HasColumnName("b2c");
            entity.Property(e => e.ChangeEmail).HasColumnName("change_email");
            entity.Property(e => e.Cipa).HasColumnName("cipa");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.Ico)
                .HasMaxLength(8)
                .HasColumnName("ico");
            entity.Property(e => e.Id).HasColumnName("id");
            entity.Property(e => e.IdMkProducer).HasColumnName("id_mk_producer");
            entity.Property(e => e.IdMkStand).HasColumnName("id_mk_stand");
            entity.Property(e => e.IdStand).HasColumnName("id_stand");
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

        modelBuilder.Entity<ViPtgStandCompany1>(entity =>
        {
            entity
                .HasNoKey()
                .ToView("vi_ptg_stand_company_");

            entity.Property(e => e.B2b).HasColumnName("b2b");
            entity.Property(e => e.B2c).HasColumnName("b2c");
            entity.Property(e => e.ChangeEmail).HasColumnName("change_email");
            entity.Property(e => e.Code)
                .HasMaxLength(50)
                .HasColumnName("code");
            entity.Property(e => e.Disable).HasColumnName("disable");
            entity.Property(e => e.Id).HasColumnName("id");
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

        OnModelCreatingPartial(modelBuilder);
    }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder);
}
