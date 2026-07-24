using System;
using System.Collections.Generic;

namespace Jas.Data.JasDb;

public partial class ZboziShopitem
{
    public string? ItemId { get; set; }

    public string? Productname { get; set; }

    public string? Description { get; set; }

    public string? Url { get; set; }

    public string? PriceVat { get; set; }

    public string? Manufacturer { get; set; }

    public string? Imgurl { get; set; }

    public string? Productno { get; set; }

    public string? DeliveryDate { get; set; }

    public string? Ean { get; set; }

    public string? RegNumber { get; set; }

    public bool? Outlet { get; set; }
}
