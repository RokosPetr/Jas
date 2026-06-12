using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PovSortItemsImportCsv
{
    public string ProductId { get; set; } = null!;

    public decimal MinQty { get; set; }

    public decimal MinStockQty { get; set; }
}
