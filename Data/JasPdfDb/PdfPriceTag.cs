using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPriceTag
{
    public string RegNumber { get; set; } = null!;

    public string? Name { get; set; }

    public string? Size { get; set; }

    public int? Price { get; set; }

    public int? PriceJas { get; set; }

    public int? PriceNn { get; set; }

    public string? Unit { get; set; }

    public string? Qr { get; set; }

    public string? Surface { get; set; }

    public bool Frost { get; set; }

    public bool Rectification { get; set; }

    public string? Antislip { get; set; }

    public string? Abrasion { get; set; }

    public string? OrigName { get; set; }

    public bool Outlet { get; set; }

    public string? OutletQr { get; set; }

    public int? SourceType { get; set; }

    public bool Discount { get; set; }

    public bool Discarded { get; set; }

    public bool Inserted { get; set; }

    public short? TypeOrder { get; set; }

    public virtual ICollection<PdfPtPlateItem> PdfPtPlateItems { get; set; } = new List<PdfPtPlateItem>();
}
