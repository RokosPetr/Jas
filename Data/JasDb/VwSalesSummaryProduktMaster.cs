using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwSalesSummaryProduktMaster
{
    public string? KlicProduktu { get; set; }

    public string? Abbr { get; set; }

    public string? Abbr2 { get; set; }

    public string? Reg { get; set; }

    public string? Nazev { get; set; }

    public string? Serie { get; set; }

    public string? Mj { get; set; }

    public string? Regdod { get; set; }

    public string? Vyrobce { get; set; }

    public int JeVmop { get; set; }

    public int JeVk2 { get; set; }

    public string? StatusZbozi { get; set; }

    public string? Typ { get; set; }

    public string Kategorie { get; set; } = null!;

    public int PoradiKategorie { get; set; }
}
