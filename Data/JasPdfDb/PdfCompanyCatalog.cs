using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfCompanyCatalog
{
    public int Id { get; set; }

    public int IdCompany { get; set; }

    public int IdCatalog { get; set; }

    public int IdFile { get; set; }

    public int PageStartOn { get; set; }

    public int? PageIndexFrom { get; set; }

    public int? PageIndexTo { get; set; }

    public int? BeginIndexFrom { get; set; }

    public int? BeginIndexTo { get; set; }

    public int? EndingIndexFrom { get; set; }

    public int? EndingIndexTo { get; set; }

    public int PageOrder { get; set; }

    public virtual PdfCatalog IdCatalogNavigation { get; set; } = null!;

    public virtual PdfCompany IdCompanyNavigation { get; set; } = null!;

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
