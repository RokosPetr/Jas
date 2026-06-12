using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class ViPovSortExportPovinnySortiment
{
    public string ProductId { get; set; } = null!;

    public string? IndexMop { get; set; }

    public string? Název { get; set; }

    public string? Mj { get; set; }

    public string? Kategorie { get; set; }

    public string? Výrobce { get; set; }

    public string? Série { get; set; }

    public int Povinno { get; set; }

    public decimal? MnožstvíProExport { get; set; }

    public decimal? KoeficientMj { get; set; }

    public decimal? Minimum { get; set; }

    public int VersionId { get; set; }
}
