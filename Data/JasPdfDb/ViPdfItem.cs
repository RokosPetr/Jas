using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfItem
{
    public int IdFile { get; set; }

    public int IdCatalog { get; set; }

    public int PageIndex { get; set; }

    public string ReplaceCode { get; set; } = null!;

    public string? RegNumber { get; set; }

    public int? PageIndexInFile { get; set; }
}
