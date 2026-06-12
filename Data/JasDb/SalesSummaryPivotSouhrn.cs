using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class SalesSummaryPivotSouhrn
{
    public string? KlíčProduktu { get; set; }

    public string? Název { get; set; }

    public string? Série { get; set; }

    public string? Mj { get; set; }

    public string? Výrobce { get; set; }

    public string? StatusZboží { get; set; }

    public string? Typ { get; set; }

    public string Kategorie { get; set; } = null!;

    public string Pobočka { get; set; } = null!;

    public int? Rok { get; set; }

    public int? Měsíc { get; set; }

    public decimal? Množství { get; set; }

    public decimal? Částka { get; set; }

    public string? KatalogovéČíslo { get; set; }
}
