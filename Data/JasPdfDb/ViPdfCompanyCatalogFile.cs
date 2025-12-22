using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfCompanyCatalogFile
{
    public string CompanyKey { get; set; } = null!;

    public string CatalogKey { get; set; } = null!;

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

    public string Path { get; set; } = null!;

    public string? CatalogType { get; set; }

    public string? ContentGroup { get; set; }

    public string? Series { get; set; }

    public string? PdfType { get; set; }
}
