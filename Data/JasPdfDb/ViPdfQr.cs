using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfQr
{
    public int? PageIndexInFile { get; set; }

    public string CompanyKey { get; set; } = null!;

    public string CatalogKey { get; set; } = null!;

    public string? ReplaceValue { get; set; }

    public int IdCompany { get; set; }

    public int IdCatalog { get; set; }

    public int IdFile { get; set; }

    public int PageIndex { get; set; }

    public string ObjectKey { get; set; } = null!;
}
