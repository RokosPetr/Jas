using System;
using System.Collections.Generic;

namespace Jas.Data.JasMtzDb;

public partial class PdfCompanyCatalog
{
    public int Id { get; set; }

    public int IdCompany { get; set; }

    public int IdCatalog { get; set; }

    public int IdFile { get; set; }

    public string? PartName { get; set; }

    public int PageStartOn { get; set; }

    public int PageIndexFrom { get; set; }

    public int PageIndexTo { get; set; }

    public virtual PdfCatalog IdCatalogNavigation { get; set; } = null!;

    public virtual PdfCompany IdCompanyNavigation { get; set; } = null!;

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
