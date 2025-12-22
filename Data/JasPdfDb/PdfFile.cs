using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfFile
{
    public int Id { get; set; }

    public int? IdParent { get; set; }

    public string Path { get; set; } = null!;

    public int CountPages { get; set; }

    public string? PdfType { get; set; }

    public string? CatalogType { get; set; }

    public string? ContentGroup { get; set; }

    public string? Series { get; set; }

    public long? Size { get; set; }

    public DateTime? Modified { get; set; }

    public bool Disable { get; set; }

    public virtual ICollection<PdfCompanyCatalog> PdfCompanyCatalogs { get; set; } = new List<PdfCompanyCatalog>();

    public virtual ICollection<PdfContent> PdfContents { get; set; } = new List<PdfContent>();

    public virtual ICollection<PdfItemPrice> PdfItemPrices { get; set; } = new List<PdfItemPrice>();

    public virtual ICollection<PdfQr> PdfQrs { get; set; } = new List<PdfQr>();

    public virtual ICollection<PdfText> PdfTexts { get; set; } = new List<PdfText>();
}
