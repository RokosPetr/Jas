using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfItemPrice
{
    public int IdCompany { get; set; }

    public int IdFile { get; set; }

    public int PageIndex { get; set; }

    public string ReplaceCode { get; set; } = null!;

    public string? RegNumber { get; set; }

    public int? Price { get; set; }

    public string? Unit { get; set; }

    public int? Rabat { get; set; }

    public int? PdfPrice { get; set; }

    public string? ReplaceText { get; set; }

    public DateTime? ValidDate { get; set; }

    public double? X { get; set; }

    public double? Y { get; set; }

    public virtual PdfCompany IdCompanyNavigation { get; set; } = null!;

    public virtual PdfFile IdFileNavigation { get; set; } = null!;
}
