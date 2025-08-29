using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasIdentityDb;

public partial class JasIdentityDbContext
{
    public DbSet<ViEshopUser> ViEshopUsers { get; set; }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder)
    {
        modelBuilder.Entity<ViEshopUser>(entity =>
        {
            entity.HasNoKey();
            entity.ToView("vi_eshop_user");

            entity.Property(e => e.Jmeno).HasColumnName("jmeno");
            entity.Property(e => e.Prijmeni).HasColumnName("prijmeni");
            entity.Property(e => e.ObchodniJmeno).HasColumnName("obchodni_jmeno");
            entity.Property(e => e.ZkracenyNazev).HasColumnName("zkraceny_nazev");
            entity.Property(e => e.Mesto).HasColumnName("mesto");
            entity.Property(e => e.Ico).HasColumnName("ico");
            entity.Property(e => e.Pc).HasColumnName("pc");
            entity.Property(e => e.Skp).HasColumnName("skp");
            entity.Property(e => e.Login).HasColumnName("login");
            entity.Property(e => e.Heslo).HasColumnName("heslo");
            entity.Property(e => e.EmailOdberatele).HasColumnName("email_odberatele");
            entity.Property(e => e.EmailObchodnika).HasColumnName("email_obchodnika");
            entity.Property(e => e.Objednavky).HasColumnName("objednavky");
            entity.Property(e => e.Sklad).HasColumnName("sklad");
            entity.Property(e => e.Paleta).HasColumnName("paleta");
            entity.Property(e => e.NedodanePolozky).HasColumnName("nedodane_polozky");
            entity.Property(e => e.SkladVlastniPobocky).HasColumnName("sklad_vlastni_pobocky");
            entity.Property(e => e.SkladVsech).HasColumnName("sklad_vsech");
            entity.Property(e => e.RabSkp).HasColumnName("rab_skp");
            entity.Property(e => e.UliceACislo).HasColumnName("ulice_a_cislo");
            entity.Property(e => e.Psc).HasColumnName("psc");
            entity.Property(e => e.Telefon).HasColumnName("telefon");
            entity.Property(e => e.K2IdZakaznika).HasColumnName("k2_id_zakaznika");
            entity.Property(e => e.K2IdCenoveSkupiny).HasColumnName("k2_id_cenove_skupiny");
        });
    }
}
