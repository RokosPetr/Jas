using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtStandHistory
{
    public int? IdPtStand { get; set; }

    public int? IdPtPlate { get; set; }

    public int? IdPtPlateItem { get; set; }

    public string? RegNumber { get; set; }

    public string ColumnName { get; set; } = null!;

    public string? OldValue { get; set; }

    public string? NewValue { get; set; }

    public DateTime ChangeDate { get; set; }

    public DateTime? CheckDate { get; set; }
}
