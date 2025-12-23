using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class ViPdfCompanyCatalog
{
    public int IdCompany { get; set; }

    public string CompanyKey { get; set; } = null!;

    public string CompanyCode { get; set; } = null!;

    public int IdCatalog { get; set; }

    public string CatalogKey { get; set; } = null!;

    public string CatalogTitle { get; set; } = null!;
}
