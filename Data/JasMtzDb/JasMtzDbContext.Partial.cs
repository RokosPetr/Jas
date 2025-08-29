using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasMtzDb
{
    public partial class JasMtzDbContext
    {
        partial void OnModelCreatingPartial(ModelBuilder modelBuilder)
        {
            modelBuilder.Entity<MtzProductAttribute>()
                .HasKey(pa => new { pa.IdProduct, pa.IdAttribute });

            modelBuilder.Entity<MtzProduct>()
                .HasMany(p => p.MtzProductAttributes)
                .WithOne(pa => pa.IdProductNavigation)
                .HasForeignKey(pa => pa.IdProduct);

        }
    }
}
