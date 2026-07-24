using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class ViEshopZzasobyStore9RabatEshop
{
    public string Index { get; set; } = null!;

    public string? Název { get; set; }

    public string? Série { get; set; }

    public string? KatalogovéČíslo { get; set; }

    public int Sku { get; set; }

    public int Psku { get; set; }

    public string? Mj { get; set; }

    public decimal? Prec { get; set; }

    public int IdVerze { get; set; }

    public string? PopisPodskupiny { get; set; }

    public string? DruhRabatu { get; set; }

    public decimal Rabat { get; set; }

    public string? Poznámka { get; set; }
}
