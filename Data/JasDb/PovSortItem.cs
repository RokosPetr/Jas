using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PovSortItem
{
    public int ItemId { get; set; }

    public int VersionId { get; set; }

    public string ProductId { get; set; } = null!;

    public int MinQty { get; set; }

    public DateTime LastModifiedAt { get; set; }

    public decimal? BuyPrice { get; set; }

    public decimal? PackSize { get; set; }

    public decimal? PalletQty { get; set; }

    public decimal MinStockQty { get; set; }

    public virtual PovSortVersion Version { get; set; } = null!;
}
