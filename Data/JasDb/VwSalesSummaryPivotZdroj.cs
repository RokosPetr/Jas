using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwSalesSummaryPivotZdroj
{
    public string Zdroj { get; set; } = null!;

    public string? Pobocka { get; set; }

    public string? PobockaFiltr { get; set; }

    public int PoradiPobocky { get; set; }

    public int? Rok { get; set; }

    public int? Mesic { get; set; }

    public DateOnly? Datum { get; set; }

    public int? CisloDokladu { get; set; }

    public string? Reg { get; set; }

    public string? Abbr2 { get; set; }

    public string? Nazev { get; set; }

    public string? Serie { get; set; }

    public string? Mj { get; set; }

    public string Vyrobce { get; set; } = null!;

    public decimal? Mnozstvi { get; set; }

    public decimal? Castka { get; set; }

    public string StatusZbozi { get; set; } = null!;

    public string Typ { get; set; } = null!;

    public string? Kategorie { get; set; }

    public int PoradiKategorie { get; set; }
}
