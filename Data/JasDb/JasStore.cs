using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class JasStore
{
    public int Id { get; set; }

    public string Name { get; set; } = null!;

    public virtual ICollection<PohCacheMonth> PohCacheMonths { get; set; } = new List<PohCacheMonth>();

    public virtual ICollection<PohCacheRefreshLog> PohCacheRefreshLogs { get; set; } = new List<PohCacheRefreshLog>();

    public virtual ICollection<PohSourceMonth> PohSourceMonths { get; set; } = new List<PohSourceMonth>();

    public virtual PohSourceStore? PohSourceStore { get; set; }
}
