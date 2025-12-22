using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfPtPlateItem
{
    public int Id { get; set; }

    public int IdPtPlate { get; set; }

    public int IdMkPlateItem { get; set; }

    public string RegNumber { get; set; } = null!;

    public string Name { get; set; } = null!;

    public int ItemOrder { get; set; }

    public bool Disable { get; set; }

    public bool SeriesItem { get; set; }

    public virtual PdfPtPlate IdPtPlateNavigation { get; set; } = null!;

    public virtual PdfPriceTag RegNumberNavigation { get; set; } = null!;
}
