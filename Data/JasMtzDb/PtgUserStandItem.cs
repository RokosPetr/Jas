using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PtgUserStandItem
{
    public int Id { get; set; }

    public int IdStand { get; set; }

    public string RegNumber { get; set; } = null!;

    public int SortOrder { get; set; }

    public virtual PtgUserStand IdStandNavigation { get; set; } = null!;
}
