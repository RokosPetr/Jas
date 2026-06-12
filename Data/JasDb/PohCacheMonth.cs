using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PohCacheMonth
{
    public int StoreId { get; set; }

    public short Rok { get; set; }

    public byte Mesic { get; set; }

    public int CntIn { get; set; }

    public int CntOut { get; set; }

    public int CntAll { get; set; }

    public DateTime LastRefreshAt { get; set; }

    public string? SourceNote { get; set; }

    public virtual JasStore Store { get; set; } = null!;
}
