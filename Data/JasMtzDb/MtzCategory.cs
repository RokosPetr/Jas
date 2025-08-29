using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class MtzCategory
{
    public int Id { get; set; }

    public int? IdParent { get; set; }

    public string Code { get; set; } = null!;

    public string Name { get; set; } = null!;

    public string? Image { get; set; }

    public int Level { get; set; }

    public string? SeoUrl { get; set; }

    public virtual MtzCategory? IdParentNavigation { get; set; }

    public virtual ICollection<MtzCategory> InverseIdParentNavigation { get; set; } = new List<MtzCategory>();

    public virtual ICollection<MtzProduct> MtzProducts { get; set; } = new List<MtzProduct>();
}
