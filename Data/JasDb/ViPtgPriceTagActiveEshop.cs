using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class ViPtgPriceTagActiveEshop
{
    public string RegNumber { get; set; } = null!;

    public decimal? PriceNn { get; set; }

    public decimal? PriceJas { get; set; }

    public int Discarded { get; set; }

    public int ToSellout { get; set; }

    public int Discount { get; set; }

    public bool Outlet { get; set; }

    public string? Qr { get; set; }

    public string? OutletQr { get; set; }

    public string? Unit { get; set; }
}
