using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class ViK2PalsortObklady
{
    public string Zkratka { get; set; } = null!;

    public string MopIndex { get; set; } = null!;

    public string? IdVyrobce { get; set; }

    public string? Vyrobce { get; set; }

    public string? KatalogoveCislo { get; set; }

    public string Nazev { get; set; } = null!;

    public string? Serie { get; set; }

    public string? Mj { get; set; }

    public decimal? Zasoba { get; set; }

    public string? Skupina { get; set; }

    public string? StatusProduktu { get; set; }

    public string? NadrizenyKlic { get; set; }
}
