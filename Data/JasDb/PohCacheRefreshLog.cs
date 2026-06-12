using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PohCacheRefreshLog
{
    public long RefreshId { get; set; }

    public int? StoreId { get; set; }

    public DateTime StartedAt { get; set; }

    public DateTime? FinishedAt { get; set; }

    public short? MYear { get; set; }

    public byte? MMonth { get; set; }

    public short? M1Year { get; set; }

    public byte? M1Month { get; set; }

    public string? SourcesUsed { get; set; }

    public string Status { get; set; } = null!;

    public string? Message { get; set; }

    public virtual JasStore? Store { get; set; }
}
