using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class PdfSlider
{
    public int Id { get; set; }

    public int IdCompany { get; set; }

    public int IdCatalog { get; set; }

    public string Img { get; set; } = null!;

    public int Type { get; set; }

    public string? Url { get; set; }

    public int SliderOrder { get; set; }

    public bool Disable { get; set; }

    public virtual PdfCatalog IdCatalogNavigation { get; set; } = null!;

    public virtual PdfCompany IdCompanyNavigation { get; set; } = null!;
}
