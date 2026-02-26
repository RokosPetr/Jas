using System;
using System.Collections.Generic;
using Microsoft.EntityFrameworkCore;

namespace Jas.Models.JasMtzDb;

public partial class JasMtzDbContext : DbContext
{
    public JasMtzDbContext(DbContextOptions<JasMtzDbContext> options)
        : base(options)
    {
    }

    public virtual DbSet<JasDepartment> JasDepartments { get; set; }

    public virtual DbSet<JasStore> JasStores { get; set; }

    public virtual DbSet<MtzAttribute> MtzAttributes { get; set; }

    public virtual DbSet<MtzCategory> MtzCategories { get; set; }

    public virtual DbSet<MtzOrder> MtzOrders { get; set; }

    public virtual DbSet<MtzOrderItem> MtzOrderItems { get; set; }

    public virtual DbSet<MtzProduct> MtzProducts { get; set; }

    public virtual DbSet<MtzProductAttribute> MtzProductAttributes { get; set; }

    public virtual DbSet<Role> Roles { get; set; }

    public virtual DbSet<RoleClaim> RoleClaims { get; set; }

    public virtual DbSet<User> Users { get; set; }

    public virtual DbSet<UserClaim> UserClaims { get; set; }

    public virtual DbSet<UserLogin> UserLogins { get; set; }

    public virtual DbSet<UserToken> UserTokens { get; set; }

    public virtual DbSet<ViMkMtzUser> ViMkMtzUsers { get; set; }

    public virtual DbSet<ViMtzUser> ViMtzUsers { get; set; }

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        modelBuilder.Entity<JasDepartment>(entity =>
        {
            entity.ToTable("jas_department");

            entity.Property(e => e.Id).HasColumnName("id");
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

        modelBuilder.Entity<JasStore>(entity =>
        {
            entity.ToTable("jas_store");

            entity.Property(e => e.Id).HasColumnName("id");
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

            entity.HasOne(d => d.IdUserNavigation).WithMany(p => p.MtzOrders)
                .HasForeignKey(d => d.IdUser)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_mtz_order_User");
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
            entity
                .HasNoKey()
                .ToTable("mtz_product_attribute");

            entity.Property(e => e.IdProduct).HasColumnName("id_product");
            entity.Property(e => e.IdProductAttribute).HasColumnName("id_product_attribute");
            entity.Property(e => e.ProductCode)
                .HasMaxLength(50)
                .HasColumnName("product_code");
            entity.Property(e => e.Value)
                .HasMaxLength(50)
                .HasColumnName("value");

            entity.HasOne(d => d.IdProductNavigation).WithMany()
                .HasForeignKey(d => d.IdProduct)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_mtz_product_attributes_mtz_product");

            entity.HasOne(d => d.IdProductAttributeNavigation).WithMany()
                .HasForeignKey(d => d.IdProductAttribute)
                .OnDelete(DeleteBehavior.ClientSetNull)
                .HasConstraintName("FK_mtz_product_attributes_mtz_product_attribute");
        });

        modelBuilder.Entity<Role>(entity =>
        {
            entity.ToTable("Role", "Identity");

            entity.HasIndex(e => e.NormalizedName, "RoleNameIndex")
                .IsUnique()
                .HasFilter("([NormalizedName] IS NOT NULL)");

            entity.Property(e => e.CreatedBy).HasMaxLength(450);
            entity.Property(e => e.Name).HasMaxLength(256);
            entity.Property(e => e.NormalizedName).HasMaxLength(256);
            entity.Property(e => e.UpdatedBy).HasMaxLength(450);
        });

        modelBuilder.Entity<RoleClaim>(entity =>
        {
            entity.ToTable("RoleClaims", "Identity");

            entity.HasIndex(e => e.RoleId, "IX_RoleClaims_RoleId");

            entity.HasOne(d => d.Role).WithMany(p => p.RoleClaims).HasForeignKey(d => d.RoleId);
        });

        modelBuilder.Entity<User>(entity =>
        {
            entity.ToTable("User", "Identity");

            entity.HasIndex(e => e.NormalizedEmail, "EmailIndex");

            entity.HasIndex(e => e.NormalizedUserName, "UserNameIndex")
                .IsUnique()
                .HasFilter("([NormalizedUserName] IS NOT NULL)");

            entity.Property(e => e.CreatedBy).HasMaxLength(450);
            entity.Property(e => e.DeletedBy).HasMaxLength(450);
            entity.Property(e => e.Email).HasMaxLength(256);
            entity.Property(e => e.IdDepartment).HasColumnName("id_department");
            entity.Property(e => e.IdStore).HasColumnName("id_store");
            entity.Property(e => e.NormalizedEmail).HasMaxLength(256);
            entity.Property(e => e.NormalizedUserName).HasMaxLength(256);
            entity.Property(e => e.StoreId).HasDefaultValue(0);
            entity.Property(e => e.UpdatedBy).HasMaxLength(450);
            entity.Property(e => e.UserName).HasMaxLength(256);

            entity.HasOne(d => d.IdDepartmentNavigation).WithMany(p => p.Users)
                .HasForeignKey(d => d.IdDepartment)
                .HasConstraintName("FK_User_jas_department");

            entity.HasOne(d => d.IdStoreNavigation).WithMany(p => p.Users)
                .HasForeignKey(d => d.IdStore)
                .HasConstraintName("FK_User_jas_store");

            entity.HasMany(d => d.Roles).WithMany(p => p.Users)
                .UsingEntity<Dictionary<string, object>>(
                    "UserRole",
                    r => r.HasOne<Role>().WithMany().HasForeignKey("RoleId"),
                    l => l.HasOne<User>().WithMany().HasForeignKey("UserId"),
                    j =>
                    {
                        j.HasKey("UserId", "RoleId");
                        j.ToTable("UserRoles", "Identity");
                        j.HasIndex(new[] { "RoleId" }, "IX_UserRoles_RoleId");
                    });
        });

        modelBuilder.Entity<UserClaim>(entity =>
        {
            entity.ToTable("UserClaims", "Identity");

            entity.HasIndex(e => e.UserId, "IX_UserClaims_UserId");

            entity.HasOne(d => d.User).WithMany(p => p.UserClaims).HasForeignKey(d => d.UserId);
        });

        modelBuilder.Entity<UserLogin>(entity =>
        {
            entity.HasKey(e => new { e.LoginProvider, e.ProviderKey });

            entity.ToTable("UserLogins", "Identity");

            entity.HasIndex(e => e.UserId, "IX_UserLogins_UserId");

            entity.HasOne(d => d.User).WithMany(p => p.UserLogins).HasForeignKey(d => d.UserId);
        });

        modelBuilder.Entity<UserToken>(entity =>
        {
            entity.HasKey(e => new { e.UserId, e.LoginProvider, e.Name });

            entity.ToTable("UserTokens", "Identity");

            entity.HasOne(d => d.User).WithMany(p => p.UserTokens).HasForeignKey(d => d.UserId);
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

        OnModelCreatingPartial(modelBuilder);
    }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder);
}
