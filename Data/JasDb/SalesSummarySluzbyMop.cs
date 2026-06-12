using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class SalesSummarySluzbyMop
{
    public string Reg { get; set; } = null!;

    public string? Nazev { get; set; }

    public string? DlouhyNazev { get; set; }

    public short? Sku { get; set; }

    public short? Psku { get; set; }

    public string? Mj { get; set; }
}
