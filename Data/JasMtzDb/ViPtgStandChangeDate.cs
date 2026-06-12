using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class ViPtgStandChangeDate
{
    public int? IdStand { get; set; }

    public DateOnly? ChangeDate { get; set; }

    public string Ico { get; set; } = null!;

    public string? Voj { get; set; }

    public bool? ChangeEmail { get; set; }
}
