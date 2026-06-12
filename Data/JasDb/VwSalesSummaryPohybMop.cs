using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwSalesSummaryPohybMop
{
    public string? Pobocka { get; set; }

    public string? PobockaFiltr { get; set; }

    public short PoradiPobocky { get; set; }

    public short Rok { get; set; }

    public int? Mesic { get; set; }

    public DateOnly Datum { get; set; }

    public int CisloPohybu { get; set; }

    public int CisloDokladu { get; set; }

    public string Reg { get; set; } = null!;

    public string Dopl { get; set; } = null!;

    public string? Regdod { get; set; }

    public short Sku { get; set; }

    public short? Psku { get; set; }

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
