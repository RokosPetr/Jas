using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class ViPtgPriceTag
{
    public string RegNumber { get; set; } = null!;

    public string? Name { get; set; }

    public string? Size { get; set; }

    public int? Price { get; set; }

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

    public int? PriceJas { get; set; }

    public int? PriceNn { get; set; }

    public bool Discount { get; set; }

    public bool Discarded { get; set; }

    public short? TypeOrder { get; set; }

    public string? Description { get; set; }

    public int StandId { get; set; }

    public int PlateId { get; set; }

    public int ItemId { get; set; }
}
