using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class VwRabatAktualni
{
    public short Sku { get; set; }

    public short Psku { get; set; }

    public string? ProskuK2 { get; set; }

    public string KodRabatu { get; set; } = null!;

    public string NazevRabatu { get; set; } = null!;

    public decimal? Rabat { get; set; }

    public string? Jednotka { get; set; }

    public string? Poznamka { get; set; }

    public int IdVerze { get; set; }

    public DateTime DatumOd { get; set; }

    public string PopisPodskupiny { get; set; } = null!;
}
