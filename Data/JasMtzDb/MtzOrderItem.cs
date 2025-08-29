using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class MtzOrderItem
{
    public int Id { get; set; }

    public int IdOrder { get; set; }

    public int IdProduct { get; set; }

    public string Name { get; set; } = null!;

    public int Amount { get; set; }

    public int PackageSize { get; set; }

    public string OrderUnit { get; set; } = null!;

    public string? Description { get; set; }

    public int State { get; set; }

    public string? SelectedSize { get; set; }

    public string? NamesOfEmployees { get; set; }

    public string? Comment { get; set; }

    public virtual MtzOrder IdOrderNavigation { get; set; } = null!;

    public virtual MtzProduct IdProductNavigation { get; set; } = null!;
}
