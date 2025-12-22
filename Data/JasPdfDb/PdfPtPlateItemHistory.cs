using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtPlateItemHistory
{
    public int IdPtPlate { get; set; }

    public string RegNumber { get; set; } = null!;

    public string ColumnName { get; set; } = null!;

    public string? OldValue { get; set; }

    public string? NewValue { get; set; }

    public DateTime ChangeDate { get; set; }
}
