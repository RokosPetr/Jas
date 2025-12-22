using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPriceTagHistory
{
    public string RegNumber { get; set; } = null!;

    public string ColumnName { get; set; } = null!;

    public string? OldValue { get; set; }

    public string? NewValue { get; set; }

    public DateTime ChangeDate { get; set; }

    public virtual PdfPriceTag RegNumberNavigation { get; set; } = null!;
}
