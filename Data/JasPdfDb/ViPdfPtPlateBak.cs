using System;
using System.Collections.Generic;

namespace Jas.Data.JasPdfDb;

public partial class ViPdfPtPlateBak
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

    public string? ProductType { get; set; }

    public int IdPtPlate { get; set; }

    public int IdPtStand { get; set; }

    public int IdMkStand { get; set; }

    public int? TypeOrder { get; set; }

    public int PlateOrder { get; set; }

    public int ItemOrder { get; set; }

    public string? PlateQr { get; set; }

    public string? Picture { get; set; }
}
