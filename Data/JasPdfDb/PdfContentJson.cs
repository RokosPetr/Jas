using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfContentJson
{
    public int Id { get; set; }

    public int? IdCatalog { get; set; }

    public int? IdCompany { get; set; }

    public string? CatalogKey { get; set; }

    public string? CompanyKey { get; set; }

    public string? Json { get; set; }
}
