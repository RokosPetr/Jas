using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PovSortProductsCache
{
    public string ProductId { get; set; } = null!;

    public string? Reg { get; set; }

    public string? Short1 { get; set; }

    public string? ProductName { get; set; }

    public string? CatalogNo { get; set; }

    public string? ManufacturerId { get; set; }

    public string? Brand { get; set; }

    public string? Series { get; set; }

    public string? Unit { get; set; }

    public string? CategoryKey { get; set; }

    public string? Sku { get; set; }

    public string? Psku { get; set; }

    public decimal? BuyPrice { get; set; }

    public decimal? SellPrice { get; set; }

    public decimal? PackSize { get; set; }

    public decimal? PalletQty { get; set; }

    public string? ProductStatus { get; set; }

    public string? ProductGroup { get; set; }

    public DateTime RefreshedAtUtc { get; set; }
}
