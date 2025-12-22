using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfContent
{
    public int Id { get; set; }

    public int? IdParent { get; set; }

    public int IdFile { get; set; }

    public int? IdCatalog { get; set; }

    public int? IdCompany { get; set; }

    public int PageIndexFrom { get; set; }

    public int? PageIndexTo { get; set; }

    public string Title { get; set; } = null!;

    public int? ContentLevel { get; set; }

    public int? ContentOrder { get; set; }

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
