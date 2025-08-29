using System;
using System.Collections.Generic;
using Jas.Models.Ptg;
using Microsoft.EntityFrameworkCore;

namespace Jas.Data.JasMtzDb;

public partial class JasMtzDbContext
{
    public DbSet<StandCompany> StandCompany { get; set; }

    partial void OnModelCreatingPartial(ModelBuilder modelBuilder)
    {
        modelBuilder.Entity<StandCompany>().HasNoKey();
    }
}