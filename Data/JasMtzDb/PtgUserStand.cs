using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PtgUserStand
{
    public int Id { get; set; }

    public string UserId { get; set; } = null!;

    public string Name { get; set; } = null!;

    public DateTime CreatedAt { get; set; }

    public DateTime UpdatedAt { get; set; }

    public virtual ICollection<PtgUserStandItem> PtgUserStandItems { get; set; } = new List<PtgUserStandItem>();
}
