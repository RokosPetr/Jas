using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwSalesSummaryPohybK2
{
    public string Pobocka { get; set; } = null!;

    public string PobockaFiltr { get; set; } = null!;

    public int PoradiPobocky { get; set; }

    public int? Rok { get; set; }

    public int? Mesic { get; set; }

    public DateOnly? Datum { get; set; }

    public string? CisloDokladu { get; set; }

    public string? Abbr { get; set; }

    public string? Abbr2 { get; set; }

    public string? Regdod { get; set; }

    public string Nazev { get; set; } = null!;

    public string? Serie { get; set; }

    public string? Mj { get; set; }

    public string Vyrobce { get; set; } = null!;

    public decimal? Mnozstvi { get; set; }

    public decimal? Castka { get; set; }

    public string StatusZbozi { get; set; } = null!;

    public string Typ { get; set; } = null!;

    public string Kategorie { get; set; } = null!;

    public int PoradiKategorie { get; set; }
}
