using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class FeedProduktyObrazky
{
    public string ProduktIndex { get; set; } = null!;

    public bool Hlavni { get; set; }

    public string Url { get; set; } = null!;
}
