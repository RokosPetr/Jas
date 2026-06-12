using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PohSourceStore
{
    public int StoreId { get; set; }

    public string LinkedServer { get; set; } = null!;

    public bool IsActive { get; set; }

    public string? Note { get; set; }

    public virtual JasStore Store { get; set; } = null!;
}
