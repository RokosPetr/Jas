using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfText
{
    public int Id { get; set; }

    public int IdFile { get; set; }

    public int PageIndex { get; set; }

    public string Value { get; set; } = null!;

    public double? X { get; set; }

    public double? Y { get; set; }

    public string? FontName { get; set; }

    public double? FontSize { get; set; }

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
