using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfCompany
{
    public int Id { get; set; }

    public string? Name { get; set; }

    public string? Email { get; set; }

    public string? Phone { get; set; }

    public string CompanyKey { get; set; } = null!;

    public string? ImgLogo { get; set; }

    public string? CssStyle { get; set; }

    public string? Url { get; set; }

    public bool Disable { get; set; }

    public virtual ICollection<PdfCompanyCatalog> PdfCompanyCatalogs { get; set; } = new List<PdfCompanyCatalog>();

    public virtual ICollection<PdfItemPrice> PdfItemPrices { get; set; } = new List<PdfItemPrice>();

    public virtual ICollection<PdfPtFile> PdfPtFiles { get; set; } = new List<PdfPtFile>();

    public virtual ICollection<PdfQr> PdfQrs { get; set; } = new List<PdfQr>();

    public virtual ICollection<PdfSlider> PdfSliders { get; set; } = new List<PdfSlider>();
}
