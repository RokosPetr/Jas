using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class MtzProduct
{
    public int Id { get; set; }

    public int? IdCategory { get; set; }

    public string Code { get; set; } = null!;

    public string Name { get; set; } = null!;

    public string? Description { get; set; }

    public string? Specification { get; set; }

    public string? Image { get; set; }

    public string? SeoUrl { get; set; }

    public int? PackageSize { get; set; }

    public string? OrderUnit { get; set; }

    public int? MinAmount { get; set; }

    public int? Filter { get; set; }

    public virtual MtzCategory? IdCategoryNavigation { get; set; }

    public virtual ICollection<MtzOrderItem> MtzOrderItems { get; set; } = new List<MtzOrderItem>();

    public virtual ICollection<MtzProductAttribute> MtzProductAttributes { get; set; } = new List<MtzProductAttribute>();
}
