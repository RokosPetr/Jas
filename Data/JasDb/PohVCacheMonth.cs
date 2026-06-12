using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PohVCacheMonth
{
    public int StoreId { get; set; }

    public string StoreName { get; set; } = null!;

    public short Rok { get; set; }

    public byte Mesic { get; set; }

    public int CntIn { get; set; }

    public int CntOut { get; set; }

    public int CntAll { get; set; }

    public DateTime LastRefreshAt { get; set; }
}
