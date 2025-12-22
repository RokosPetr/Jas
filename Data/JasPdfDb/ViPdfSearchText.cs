using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfSearchText
{
    public int IdFile { get; set; }

    public int PageIndex { get; set; }

    public string Value { get; set; } = null!;

    public int? PageIndexInFile { get; set; }

    public string CatalogKey { get; set; } = null!;

    public int IdCompany { get; set; }

    public int IdCatalog { get; set; }

    public double? X { get; set; }

    public double? Y { get; set; }
}
