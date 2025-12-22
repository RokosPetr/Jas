using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfDownload
{
    public int? IdCatalog { get; set; }

    public int? IdCompany { get; set; }

    public int? IdFile { get; set; }

    public string? CatalogKey { get; set; }

    public string? CatalogTitle { get; set; }

    public string? CompanyKey { get; set; }

    public string? CompanyName { get; set; }

    public string? FilePath { get; set; }

    public int? PageIndex { get; set; }

    public int? PageIndexInCatalog { get; set; }

    public int? PageIndexInBody { get; set; }

    public string? CatalogName { get; set; }
}
