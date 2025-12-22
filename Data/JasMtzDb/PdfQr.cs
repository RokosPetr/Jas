using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PdfQr
{
    public int Id { get; set; }

    public int IdFile { get; set; }

    public int PageIndex { get; set; }

    public string? Value { get; set; }

    public double? PosX { get; set; }

    public double? PosY { get; set; }

    public double? Width { get; set; }

    public double? Height { get; set; }

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
