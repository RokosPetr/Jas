using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class PohSourceMonth
{
    public int StoreId { get; set; }

    public short Rok { get; set; }

    public byte Mesic { get; set; }

    public string PoTableName { get; set; } = null!;

    public bool ExistsOnSource { get; set; }

    public DateTime LastCheckedAt { get; set; }

    public virtual JasStore Store { get; set; } = null!;
}
