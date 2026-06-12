using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class SalesSummaryAgregaceMop
{
    public string Pobocka { get; set; } = null!;

    public short Rok { get; set; }

    public int Mesic { get; set; }

    public string Reg { get; set; } = null!;

    public string Nazev { get; set; } = null!;

    public string? Serie { get; set; }

    public string Vyrobce { get; set; } = null!;

    public string Typ { get; set; } = null!;

    public string StatusZbozi { get; set; } = null!;

    public string Kategorie { get; set; } = null!;

    public decimal Mnozstvi { get; set; }

    public decimal Castka { get; set; }

    public int PoradiKategorie { get; set; }

    public int PoradiPobocky { get; set; }

    public string PobockaFiltr { get; set; } = null!;

    public string? Mj { get; set; }
}
