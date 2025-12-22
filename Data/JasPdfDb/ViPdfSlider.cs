using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfSlider
{
    public string CompanyKey { get; set; } = null!;

    public int Type { get; set; }

    public string? ImgSrc { get; set; }

    public string Url { get; set; } = null!;

    public int SliderOrder { get; set; }

    public bool Disable { get; set; }
}
