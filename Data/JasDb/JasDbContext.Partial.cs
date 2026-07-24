using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasDb
{
    public partial class JasDbContext
    {
        partial void OnModelCreatingPartial(ModelBuilder modelBuilder)
        {
            modelBuilder.Entity<ViPtgRegNumber>(entity =>
            {
                entity.HasNoKey();
                entity.ToView("vi_ptg_reg_number");
                entity.Property(e => e.RegNumber).HasColumnName("reg_number").HasMaxLength(50);
                entity.Property(e => e.Name).HasColumnName("name").HasMaxLength(200);
            });
        }
    }
}
