using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class RabatTypy
{
    public string KodRabatu { get; set; } = null!;

    public string Nazev { get; set; } = null!;

    public int Poradi { get; set; }

    public bool Aktivni { get; set; }

    public decimal? MinHodnota { get; set; }

    public decimal? MaxHodnota { get; set; }

    public string? Jednotka { get; set; }

    public int? ExcelSirka { get; set; }

    public virtual ICollection<RabatHodnoty> RabatHodnoties { get; set; } = new List<RabatHodnoty>();
}
