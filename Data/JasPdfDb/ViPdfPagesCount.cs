using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfPagesCount
{
    public string CompanyKey { get; set; } = null!;

    public string CatalogKey { get; set; } = null!;

    public int IdCatalog { get; set; }

    public int? PageStartOn { get; set; }

    public int? PagesCount { get; set; }

    public bool Landscape { get; set; }
}
