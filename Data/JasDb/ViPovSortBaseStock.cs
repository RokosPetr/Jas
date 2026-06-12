using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class ViPovSortBaseStock
{
    public string Reg { get; set; } = null!;

    public string? Regdod { get; set; }

    public string Nazov { get; set; } = null!;

    public string? Nazov2 { get; set; }

    public string? Dlnaz { get; set; }

    public short Store { get; set; }

    public short Sku { get; set; }

    public short Psku { get; set; }

    public string Dopl { get; set; } = null!;

    public string? Variant { get; set; }

    public decimal? Zasoba { get; set; }

    public string? Mj { get; set; }

    public decimal? Nakc { get; set; }

    public decimal? Prec { get; set; }

    public string? Sektor { get; set; }

    public decimal? Vbal { get; set; }

    public string? Jkpov { get; set; }

    public decimal? Vaha { get; set; }
}
