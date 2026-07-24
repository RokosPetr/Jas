using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PovSortVoSummaryCache
{
    public int SummaryCacheId { get; set; }

    public int VersionId { get; set; }

    public string Kategorie { get; set; } = null!;

    public string Sortiment { get; set; } = null!;

    public int CelkemSerií { get; set; }

    public int CelkemPalet { get; set; }

    public int KoupelnyPalet { get; set; }

    public int DlažbyPalet { get; set; }

    public decimal CelkemKčPlochy { get; set; }

    public decimal CelkemKčDekorace { get; set; }

    public decimal Celkem { get; set; }

    public DateTime CachedAtUtc { get; set; }

    public int? SortSkuInt { get; set; }

    public string? CachedBy { get; set; }

    public virtual PovSortVoVersion Version { get; set; } = null!;
}
