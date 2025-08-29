using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PtgStandCompany
{
    public int Id { get; set; }

    public int IdStand { get; set; }

    public int IdMkStand { get; set; }

    public string Ico { get; set; } = null!;

    public int Cipa { get; set; }
}
