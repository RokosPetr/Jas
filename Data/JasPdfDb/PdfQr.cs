using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfQr
{
    public int Id { get; set; }

    public int IdCompany { get; set; }

    public int IdFile { get; set; }

    public int PageIndex { get; set; }

    public string ObjectKey { get; set; } = null!;

    public string? Value { get; set; }

    public string? ReplaceValue { get; set; }

    public DateTime? ValidDate { get; set; }

    public string? Redirect { get; set; }

    public virtual PdfCompany IdCompanyNavigation { get; set; } = null!;

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
