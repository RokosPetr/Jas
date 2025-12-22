using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PdfRegNumber
{
    public int Id { get; set; }

    public int IdFile { get; set; }

    public int PageIndex { get; set; }

    public string RegNumber { get; set; } = null!;

    public string? EshopSeriesName { get; set; }

    public string? EshopSeriesCode { get; set; }

    public int? Price { get; set; }

    public string? Unit { get; set; }

    public short Rabat { get; set; }

    public string? JasPrice { get; set; }

    public string? NnPrice { get; set; }

    public DateTime ChangeDate { get; set; }

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
