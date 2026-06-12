using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwSalesSummaryProductsInfoMop
{
    public short Pobocka { get; set; }

    public string Reg { get; set; } = null!;

    public string Dopl { get; set; } = null!;

    public short Sku { get; set; }

    public short Psku { get; set; }

    public string? Nazev { get; set; }

    public string? Mj { get; set; }

    public string? Vyrobce { get; set; }

    public string? Varianta { get; set; }
}
