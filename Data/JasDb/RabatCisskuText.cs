using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class RabatCisskuText
{
    public int Id { get; set; }

    public short Sku { get; set; }

    public string? Psku { get; set; }

    public string PopisPodskupiny { get; set; } = null!;

    public int? FGroup { get; set; }

    public string? K2 { get; set; }

    public int? Poradi { get; set; }
}
