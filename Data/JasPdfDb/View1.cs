using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class View1
{
    public int IdCompany { get; set; }

    public string CompanyKey { get; set; } = null!;

    public int IdCatalog { get; set; }

    public string CatalogKey { get; set; } = null!;

    public int IdFile { get; set; }

    public string Path { get; set; } = null!;

    public int PageIndex { get; set; }

    public int? GlobalPageIndex { get; set; }

    public string? PqValue { get; set; }

    public int? TextFile { get; set; }

    public int? TextPageIndexLocal { get; set; }

    public int? TextPageIndexGlobal { get; set; }

    public string? ConcatPtValue { get; set; }
}
